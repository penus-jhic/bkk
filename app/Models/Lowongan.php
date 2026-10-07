<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lowongan extends Model
{
    use HasFactory;

    protected $table = 'lowongan';
    protected $guarded = [];

    protected $casts = [
        'persyaratan_json' => 'array',
        'benefit_json' => 'array',
        'deadline' => 'date',
        'kuota' => 'integer',
        'views_count' => 'integer',
    ];

    public function getTitleAttribute(): ?string
    {
        return $this->judul;
    }

    public function getJurusanAttribute(): ?string
    {
        return $this->target_jurusan;
    }

    public function getGajiAttribute(): ?string
    {
        return $this->gaji_kompensasi;
    }

    public function getPelamarCountAttribute(): int
    {
        return $this->lamarans()->count();
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'Aktif');
    }

    // Lowongan yang masih bisa dilamar: Aktif dan belum lewat deadline
    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('status', 'Aktif')
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }

    public function scopeTipe(Builder $query, string $tipe): Builder
    {
        return $query->where('tipe', $tipe);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function lamarans(): HasMany
    {
        return $this->hasMany(Lamaran::class, 'lowongan_id');
    }

    public function lamaran(): HasMany
    {
        return $this->hasMany(Lamaran::class, 'lowongan_id');
    }

    public function penempatanPkl(): HasMany
    {
        return $this->hasMany(PenempatanPkl::class, 'lowongan_id');
    }
}
