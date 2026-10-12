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
     * Simpan atau perbarui 1 baris draf sementara (temp = true, is_main = false) per user.
     * Jika checksum sama dengan CV utama saat ini, langsung kembalikan CV utama tanpa membuat draf.
     */
    public static function saveDraft(string $userId, string $rawMarkdown): self
    {
        $checksum = self::generateChecksum($rawMarkdown);

        $currentMain = self::where('user_id', $userId)
            ->where('temp', false)
            ->where('is_main', true)
            ->first();

        if ($currentMain && $currentMain->checksum === $checksum) {
            return $currentMain;
        }

        return DB::transaction(function () use ($userId, $rawMarkdown, $checksum) {
            $tempDraft = self::where('user_id', $userId)
                ->where('temp', true)
                ->latest('id')
                ->first();

            if ($tempDraft) {
                self::where('user_id', $userId)
                    ->where('temp', true)
                    ->where('id', '!=', $tempDraft->id)
                    ->delete();

                $tempDraft->update([
                    'raw_markdown' => $rawMarkdown,
                    'checksum' => $checksum,
                    'is_main' => false,
                    'temp' => true,
                    'score' => null,
                    'feedback' => null,
                ]);

                return $tempDraft->fresh();
            }

            return self::create([
                'user_id' => $userId,
                'raw_markdown' => $rawMarkdown,
                'checksum' => $checksum,
                'is_main' => false,
                'temp' => true,
                'score' => null,
                'feedback' => null,
            ]);
        });
    }

    /**
     * Simpan versi resmi CV (temp = false, is_main = true).
     * Jika checksum identik dengan CV utama saat ini, hindari duplikasi.
     * Jika terdapat draf sementara (temp = true), promosikan menjadi versi resmi.
     */
    public static function saveVersion(string $userId, string $rawMarkdown): self
    {
        $checksum = self::generateChecksum($rawMarkdown);

        $currentMain = self::where('user_id', $userId)
            ->where('temp', false)
            ->where('is_main', true)
            ->first();

        if ($currentMain && $currentMain->checksum === $checksum) {
            self::where('user_id', $userId)->where('temp', true)->delete();
            return $currentMain;
        }

        return DB::transaction(function () use ($userId, $rawMarkdown, $checksum) {
            self::where('user_id', $userId)->update(['is_main' => false]);

            $tempDraft = self::where('user_id', $userId)
                ->where('temp', true)
                ->latest('id')
                ->first();

            if ($tempDraft) {
                self::where('user_id', $userId)
                    ->where('temp', true)
                    ->where('id', '!=', $tempDraft->id)
                    ->delete();

                $tempDraft->update([
                    'raw_markdown' => $rawMarkdown,
                    'checksum' => $checksum,
                    'temp' => false,
                    'is_main' => true,
                    'score' => null,
                    'feedback' => null,
                ]);

                return $tempDraft->fresh();
            }

            return self::create([
                'user_id' => $userId,
                'raw_markdown' => $rawMarkdown,
                'checksum' => $checksum,
                'is_main' => true,
                'temp' => false,
                'score' => null,
                'feedback' => null,
            ]);
        });
    }

    /**
     * Jadikan record ini sebagai CV Utama (is_main = true) dan nonaktifkan versi lain milik user.
     */
    public function setAsMain(): self
    {
        return DB::transaction(function () {
            self::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_main' => false]);

            $this->update([
                'is_main' => true,
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

    public function getKontenMarkdownAttribute(): string
    {
        return (string) ($this->attributes['raw_markdown'] ?? '');
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

    public function getSkorTotalAiAttribute(): int
    {
        if ($this->score !== null) {
            return (int) $this->score;
        }

        $feedback = $this->feedback;
        if (is_array($feedback) && isset($feedback['total_score'])) {
            return (int) $feedback['total_score'];
        }
        return 88;
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
