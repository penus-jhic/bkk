<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Mitra extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'mitra';
    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'npwp',
        'pic_name',
        'pic_role',
        'pic_email',
        'pic_phone',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'tanggal_mou_mulai' => 'date',
        'tanggal_mou_selesai' => 'date',
    ];

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function lowongans(): HasMany
    {
        return $this->hasMany(Lowongan::class, 'mitra_id');
    }

    public function penempatanPkl(): HasMany
    {
        return $this->hasMany(PenempatanPkl::class, 'mitra_id');
    }

    /**
     * Normalisasi URL logo agar selalu berprefix /bkk jika mengarah ke uploads lokal
     */
    protected function logoUrl(): Attribute
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
