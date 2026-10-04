<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PklLaporanAkhir extends Model
{
    use HasFactory;

    protected $table = 'pkl_laporan_akhir';
    protected $guarded = [];

    protected $casts = [
        'nomor_bab' => 'integer',
        'terakhir_diperbarui' => 'date',
    ];

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPkl::class, 'penempatan_pkl_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(ProfilSiswa::class, 'siswa_id', 'user_id');
    }

    /**
     * Normalisasi URL draft file agar selalu berprefix /bkk jika mengarah ke uploads lokal
     */
    protected function fileDraftUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (empty($value)) {
                    return $value;
                }
                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }
                if (str_starts_with($value, '/bkk/')) {
                    return $value;
                }
                if (str_starts_with($value, '/uploads/')) {
                    return '/bkk' . $value;
                }
                if (str_starts_with($value, 'uploads/')) {
                    return '/bkk/' . $value;
                }
                return $value;
            }
        );
    }
}
