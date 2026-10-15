<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CvResume extends Model
{
    use HasFactory;

    protected $table = 'cv_resumes';
    protected $guarded = [];

    protected $casts = [
        'is_main' => 'boolean',
        'temp' => 'boolean',
        'score' => 'integer',
        'feedback' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (!empty($model->raw_markdown) && empty($model->checksum)) {
                $model->checksum = self::generateChecksum($model->raw_markdown);
            }
        });
    }

    public function scopeSaved($query)
    {
        return $query->where('temp', false);
    }

    public function scopeTemp($query)
    {
        return $query->where('temp', true);
    }

    /**
     * Hitung SHA-256 checksum dari raw markdown dengan normalisasi baris baru.
     */
    public static function generateChecksum(string $rawMarkdown): string
    {
        $normalized = trim(str_replace("\r\n", "\n", $rawMarkdown));
        return hash('sha256', $normalized);
    }

    /**
     * Pastikan profil_siswa ada sebelum operasi insert pada cv_resumes
     * demi integritas referensial foreign key.
     */
    public static function ensureProfilSiswaExists(string $userId): void
    {
        static $ensured = [];
        if (isset($ensured[$userId])) {
            return;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('profil_siswa')) {
                ProfilSiswa::firstOrCreate(
                    ['user_id' => $userId],
                    [
                        'nis' => '-',
                        'jurusan' => 'Belum diisi',
                        'angkatan' => (string) date('Y'),
                        'status_kelulusan' => 'SISWA_AKTIF',
                    ]
                );
                $ensured[$userId] = true;
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function getUserColumn(): string
    {
        static $col = null;
        if ($col === null) {
            $col = \Illuminate\Support\Facades\Schema::hasColumn('cv_resumes', 'user_id') ? 'user_id' : 'siswa_id';
        }
        return $col;
    }

    public static function getMarkdownColumn(): string
    {
        static $col = null;
        if ($col === null) {
            $col = \Illuminate\Support\Facades\Schema::hasColumn('cv_resumes', 'raw_markdown') ? 'raw_markdown' : 'konten_markdown';
        }
        return $col;
    }

    public static function getMainColumn(): string
    {
        static $col = null;
        if ($col === null) {
            $col = \Illuminate\Support\Facades\Schema::hasColumn('cv_resumes', 'is_main') ? 'is_main' : 'is_primary';
        }
        return $col;
    }

    /**
     * Simpan atau perbarui 1 baris draf sementara (temp = true, is_main = false) per user.
     * Jika checksum sama dengan CV utama saat ini, langsung kembalikan CV utama tanpa membuat draf.
     */
    public static function saveDraft(string $userId, string $rawMarkdown): self
    {
        self::ensureProfilSiswaExists($userId);

        $checksum = self::generateChecksum($rawMarkdown);
        $userCol = self::getUserColumn();
        $mainCol = self::getMainColumn();
        $mdCol = self::getMarkdownColumn();

        $currentMain = self::where($userCol, $userId)
            ->where('temp', false)
            ->where($mainCol, true)
            ->first();

        if ($currentMain && $currentMain->checksum === $checksum) {
            self::where($userCol, $userId)->where('temp', true)->delete();
            return $currentMain;
        }

        return DB::transaction(function () use ($userId, $rawMarkdown, $checksum, $userCol, $mainCol, $mdCol) {
            $tempDraft = self::where($userCol, $userId)
                ->where('temp', true)
                ->latest('id')
                ->first();

            if ($tempDraft) {
                self::where($userCol, $userId)
                    ->where('temp', true)
                    ->where('id', '!=', $tempDraft->id)
                    ->delete();

                $updateData = [
                    $mdCol => $rawMarkdown,
                    'checksum' => $checksum,
                    $mainCol => false,
                    'temp' => true,
                    'score' => null,
                    'feedback' => null,
                ];

                $tempDraft->update($updateData);

                return $tempDraft->fresh();
            }

            $createData = [
                $userCol => $userId,
                $mdCol => $rawMarkdown,
                'checksum' => $checksum,
                $mainCol => false,
                'temp' => true,
                'score' => null,
                'feedback' => null,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('cv_resumes', 'judul_cv')) {
                $createData['judul_cv'] = 'Draf CV ATS';
            }

            return self::create($createData);
        });
    }

    /**
     * Simpan versi resmi CV (temp = false, is_main = true).
     * Jika checksum identik dengan CV utama saat ini, hindari duplikasi.
     * Jika terdapat draf sementara (temp = true), promosikan menjadi versi resmi.
     */
    public static function saveVersion(string $userId, string $rawMarkdown): self
    {
        self::ensureProfilSiswaExists($userId);

        $checksum = self::generateChecksum($rawMarkdown);
        $userCol = self::getUserColumn();
        $mainCol = self::getMainColumn();
        $mdCol = self::getMarkdownColumn();

        $currentMain = self::where($userCol, $userId)
            ->where('temp', false)
            ->where($mainCol, true)
            ->first();

        if ($currentMain && $currentMain->checksum === $checksum) {
            self::where($userCol, $userId)->where('temp', true)->delete();
            return $currentMain;
        }

        // Jika terdapat versi resmi lama yang memiliki checksum identik, cukup aktifkan kembali
        $existingIdentical = self::where($userCol, $userId)
            ->where('temp', false)
            ->where('checksum', $checksum)
            ->first();

        if ($existingIdentical) {
            return DB::transaction(function () use ($userCol, $mainCol, $userId, $existingIdentical) {
                self::where($userCol, $userId)->where('temp', true)->delete();
                self::where($userCol, $userId)->where('id', '!=', $existingIdentical->id)->update([$mainCol => false]);
                $existingIdentical->update([$mainCol => true]);
                return $existingIdentical->fresh();
            });
        }

        return DB::transaction(function () use ($userId, $rawMarkdown, $checksum, $userCol, $mainCol, $mdCol) {
            self::where($userCol, $userId)->update([$mainCol => false]);

            $tempDraft = self::where($userCol, $userId)
                ->where('temp', true)
                ->latest('id')
                ->first();

            if ($tempDraft) {
                self::where($userCol, $userId)
                    ->where('temp', true)
                    ->where('id', '!=', $tempDraft->id)
                    ->delete();

                $updateData = [
                    $mdCol => $rawMarkdown,
                    'checksum' => $checksum,
                    'temp' => false,
                    $mainCol => true,
                    'score' => null,
                    'feedback' => null,
                ];

                $tempDraft->update($updateData);

                return $tempDraft->fresh();
            }

            $createData = [
                $userCol => $userId,
                $mdCol => $rawMarkdown,
                'checksum' => $checksum,
                $mainCol => true,
                'temp' => false,
                'score' => null,
                'feedback' => null,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('cv_resumes', 'judul_cv')) {
                $createData['judul_cv'] = 'CV ATS Utama';
            }

            return self::create($createData);
        });
    }

    /**
     * Jadikan record ini sebagai CV Utama (is_main = true) dan nonaktifkan versi lain milik user.
     */
    public function setAsMain(): self
    {
        $userCol = self::getUserColumn();
        $mainCol = self::getMainColumn();
        $userId = $this->{$userCol} ?? $this->user_id;

        return DB::transaction(function () use ($userCol, $mainCol, $userId) {
            self::where($userCol, $userId)
                ->where('id', '!=', $this->id)
                ->update([$mainCol => false]);

            $this->update([
                $mainCol => true,
                'temp' => false,
            ]);
            return $this->fresh();
        });
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(ProfilSiswa::class, 'user_id', 'user_id');
    }

    public function lamarans(): HasMany
    {
        return $this->hasMany(Lamaran::class, 'cv_id');
    }

    /* -------------------------------------------------------------------------- */
    /* Accessor / Mutator Kompatibilitas Transisional                              */
    /* -------------------------------------------------------------------------- */

    public function getUserIdAttribute(): ?string
    {
        return $this->attributes['user_id'] ?? $this->attributes['siswa_id'] ?? null;
    }

    public function getRawMarkdownAttribute(): string
    {
        return (string) ($this->attributes['raw_markdown'] ?? $this->attributes['konten_markdown'] ?? '');
    }

    public function getIsMainAttribute(): bool
    {
        return (bool) ($this->attributes['is_main'] ?? $this->attributes['is_primary'] ?? false);
    }

    public function getKontenMarkdownAttribute(): string
    {
        return (string) ($this->attributes['raw_markdown'] ?? $this->attributes['konten_markdown'] ?? '');
    }

    public function setKontenMarkdownAttribute($value): void
    {
        $this->attributes['raw_markdown'] = $value;
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (bool) ($this->attributes['is_main'] ?? false);
    }

    public function setIsPrimaryAttribute($value): void
    {
        $this->attributes['is_main'] = (bool) $value;
    }

    public function getSiswaIdAttribute(): ?string
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function setSiswaIdAttribute($value): void
    {
        $this->attributes['user_id'] = $value;
    }

    public function getJudulCvAttribute(): string
    {
        return 'CV ATS Utama';
    }

    public function getSkorTotalAiAttribute(): ?int
    {
        if ($this->score !== null) {
            return (int) $this->score;
        }

        $feedback = $this->feedback;
        if (is_array($feedback) && isset($feedback['total_score'])) {
            return (int) $feedback['total_score'];
        }
        return null;
    }

    public function getSkorParameterJsonAttribute(): array
    {
        $feedback = $this->feedback;
        if (is_array($feedback) && !empty($feedback['parameters']) && is_array($feedback['parameters'])) {
            return array_map(fn ($p) => [
                'id' => $p['id'] ?? '',
                'label' => $p['label'] ?? 'Parameter ATS',
                'value' => (int) ($p['score'] ?? $p['value'] ?? 85),
                'score' => (int) ($p['score'] ?? $p['value'] ?? 85),
                'max_score' => (int) ($p['max_score'] ?? 100),
                'status' => $p['status'] ?? 'GOOD',
                'weight' => $p['weight'] ?? 0.25,
                'feedback' => $p['feedback'] ?? '',
            ], $feedback['parameters']);
        }
        return [];
    }
}
