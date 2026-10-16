<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lamaran extends Model
{
    use HasFactory;

    protected $table = 'lamaran';
    protected $guarded = [];

    protected $casts = [
        'tanggal_melamar' => 'date',
        'skor_match_ai' => 'integer',
        'step_tahapan' => 'integer',
    ];

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(Lowongan::class, 'lowongan_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(ProfilSiswa::class, 'siswa_id', 'user_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CvResume::class, 'cv_id');
    }

    public function interview(): HasOne
    {
        return $this->hasOne(JadwalInterview::class, 'lamaran_id');
    }

    public function profilSiswa(): BelongsTo
    {
        return $this->siswa();
    }

    public function cvResume(): BelongsTo
    {
        return $this->cv();
    }

    public function getNamaAttribute(): string
    {
        return $this->siswa?->nama_lengkap ?: 'Pelamar BKK';
    }

    public function getStatusPendidikanAttribute(): string
    {
        return $this->siswa ? ($this->siswa->status_kelulusan === 'ALUMNI' ? 'Alumni' : 'Siswa Aktif ' . ($this->siswa->kelas ?? '')) : 'Siswa Aktif';
    }

    public function getCvScoreAttribute(): int
    {
        return (int) $this->skor_match_ai;
    }

    public function getLowonganTitleAttribute(): string
    {
        return $this->lowongan?->judul ?? 'Lowongan';
    }

    public function getNisNisnAttribute(): string
    {
        return $this->siswa ? ($this->siswa->nis ?: ($this->siswa->nisn ?: '-')) : '-';
    }

    public function getJurusanAttribute(): string
    {
        return $this->siswa?->jurusan ?? 'Umum';
    }

    public function getEmailAttribute(): string
    {
        return $this->siswa?->email ?: '-';
    }

    public function getNoHpAttribute(): string
    {
        return $this->siswa?->no_hp ?: '-';
    }

    public function getLokasiAttribute(): string
    {
        return $this->siswa?->kota ?: '-';
    }

    public function getPortfolioUrlAttribute(): string
    {
        return $this->siswa?->link_portfolio ?? '';
    }

    public function getSkillsAttribute(): array
    {
        return ['Kompetensi Kejuruan', 'Disiplin Kerja', 'Kreativitas Solutif'];
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(LamaranRiwayatStatus::class, 'lamaran_id')->latest('created_at');
    }
}
