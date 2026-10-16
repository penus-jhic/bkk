<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenempatanPkl extends Model
{
    use HasFactory;

    protected $table = 'penempatan_pkl';
    protected $guarded = [];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'target_jam' => 'integer',
        'total_jam_tercapai' => 'integer',
        'nilai_akhir_industri' => 'float',
        'nilai_akhir_sekolah' => 'float',
        'disetujui_pada' => 'datetime',
        'pengajuan_mandiri' => 'boolean',
    ];

    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->where('status', 'BERJALAN');
    }

    public function scopeMenungguPersetujuan(Builder $query): Builder
    {
        return $query->where('status', 'MENUNGGU_PERSETUJUAN');
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', 'SELESAI');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(ProfilSiswa::class, 'siswa_id', 'user_id');
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(Lowongan::class, 'lowongan_id');
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(PklJurnalHarian::class, 'penempatan_pkl_id')->orderBy('tanggal', 'desc');
    }

    public function laporans(): HasMany
    {
        return $this->hasMany(PklLaporanAkhir::class, 'penempatan_pkl_id')->orderBy('nomor_bab');
    }
}
