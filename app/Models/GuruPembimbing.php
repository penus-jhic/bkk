<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Guru yang pernah membuka Portal Guru. Terisi otomatis dari data auth service (lihat GuruPklController),
 * dipakai admin untuk memilih guru pembimbing siswa.
 */
class GuruPembimbing extends Model
{
    protected $table = 'guru_pembimbing';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function siswaBimbingan(): HasMany
    {
        return $this->hasMany(ProfilSiswa::class, 'guru_pembimbing_id', 'user_id');
    }
}
