<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Identitas siswa (nama, email, HP) disalin dari auth service saat siswa login, supaya mitra & guru bisa
     *    melihat siapa pelamarnya. Kota diisi sendiri oleh siswa (tidak ada di auth service).
     * 2. Guru pembimbing per siswa: guru hanya melihat siswa yang dibimbingnya.
     * 3. Daftar guru (guru_pembimbing) terisi otomatis saat guru membuka Portal Guru, karena auth service tidak
     *    menyediakan endpoint daftar pengguna. Admin memilih pembimbing dari daftar ini.
     */
    public function up(): void
    {
        Schema::table('profil_siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('profil_siswa', 'nama_lengkap')) {
                $table->string('nama_lengkap', 150)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('profil_siswa', 'email')) {
                $table->string('email', 150)->nullable()->after('nama_lengkap');
            }
            if (!Schema::hasColumn('profil_siswa', 'no_hp')) {
                $table->string('no_hp', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('profil_siswa', 'kota')) {
                $table->string('kota', 100)->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('profil_siswa', 'guru_pembimbing_id')) {
                $table->string('guru_pembimbing_id', 64)->nullable()->index()->after('status_aktivitas');
            }
        });

        if (!Schema::hasTable('guru_pembimbing')) {
            Schema::create('guru_pembimbing', function (Blueprint $table) {
                $table->string('user_id', 64)->primary();
                $table->string('nama_lengkap', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('no_hp', 50)->nullable();
                $table->string('nomor_induk', 50)->nullable();
                $table->timestamps();
            });
        }

        // Pengajuan dari siswa yang belum punya pembimbing tetap bisa disimpan (masuk antrean admin)
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->string('guru_pembimbing_id', 64)->nullable()->change();
        });

        // Siswa yang sudah pernah PKL: pembimbingnya diambil dari penempatan terakhir
        $latestGuru = DB::table('penempatan_pkl')
            ->whereNotNull('guru_pembimbing_id')
            ->orderBy('id')
            ->pluck('guru_pembimbing_id', 'siswa_id');

        foreach ($latestGuru as $siswaId => $guruId) {
            DB::table('profil_siswa')
                ->where('user_id', $siswaId)
                ->whereNull('guru_pembimbing_id')
                ->update(['guru_pembimbing_id' => $guruId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_pembimbing');

        Schema::table('profil_siswa', function (Blueprint $table) {
            foreach (['nama_lengkap', 'email', 'no_hp', 'kota', 'guru_pembimbing_id'] as $column) {
                if (Schema::hasColumn('profil_siswa', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
