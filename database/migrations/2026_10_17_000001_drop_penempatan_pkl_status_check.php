<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Di PostgreSQL kolom enum dibuat sebagai varchar + CHECK constraint. Migrasi 2026_10_16_000001 mengubah status
     * menjadi string biasa lewat ->change(), tetapi constraint lamanya (BERJALAN, SELESAI, DIBATALKAN, MENUNGGU_SURAT)
     * tidak ikut terhapus, sehingga status MENUNGGU_PERSETUJUAN ditolak database.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql' && Schema::hasTable('penempatan_pkl')) {
            DB::statement('ALTER TABLE penempatan_pkl DROP CONSTRAINT IF EXISTS penempatan_pkl_status_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Constraint lama sengaja tidak dipasang lagi karena akan menolak data berstatus MENUNGGU_PERSETUJUAN.
    }
};
