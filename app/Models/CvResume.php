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
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (!empty($model->raw_markdown) && empty($model->checksum)) {
                $model->checksum = self::generateChecksum($model->raw_markdown);
            }
        });
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
     * Simpan versi baru CV. Jika checksum identik dengan CV utama saat ini, hindari duplikasi.
     */
    public static function saveVersion(string $userId, string $rawMarkdown): self
    {
        $checksum = self::generateChecksum($rawMarkdown);

        $currentMain = self::where('user_id', $userId)->where('is_main', true)->first();
        if ($currentMain && $currentMain->checksum === $checksum) {
            return $currentMain;
        }

        return DB::transaction(function () use ($userId, $rawMarkdown, $checksum) {
            self::where('user_id', $userId)->update(['is_main' => false]);

            return self::create([
                'user_id' => $userId,
                'raw_markdown' => $rawMarkdown,
                'checksum' => $checksum,
                'is_main' => true,
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

            $this->update(['is_main' => true]);
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
        return 88;
    }
}
