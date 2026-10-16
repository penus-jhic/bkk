<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan kolom file berkas dan review guru pada cv_resumes
        if (Schema::hasTable('cv_resumes')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                if (!Schema::hasColumn('cv_resumes', 'file_path')) {
                    $table->string('file_path')->nullable()->after('checksum');
                }
                if (!Schema::hasColumn('cv_resumes', 'file_name')) {
                    $table->string('file_name')->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('cv_resumes', 'file_size')) {
                    $table->unsignedInteger('file_size')->nullable()->after('file_name');
                }
                if (!Schema::hasColumn('cv_resumes', 'file_mime')) {
                    $table->string('file_mime', 100)->nullable()->after('file_size');
                }
                if (!Schema::hasColumn('cv_resumes', 'catatan_guru')) {
                    $table->text('catatan_guru')->nullable()->after('file_mime');
                }
                if (!Schema::hasColumn('cv_resumes', 'status_review_guru')) {
                    $table->string('status_review_guru', 50)->default('Belum Direview')->after('catatan_guru');
                }
                if (!Schema::hasColumn('cv_resumes', 'reviewed_by_guru_id')) {
                    $table->string('reviewed_by_guru_id', 64)->nullable()->after('status_review_guru');
                }
                if (!Schema::hasColumn('cv_resumes', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_guru_id');
                }
            });
        }

        // 2. Modifikasi dan tambahkan status persetujuan guru pada penempatan_pkl
        if (Schema::hasTable('penempatan_pkl')) {
            Schema::table('penempatan_pkl', function (Blueprint $table) {
                // Ubah status ke string 50 agar fleksibel mendukung MENUNGGU_PERSETUJUAN
                $table->string('status', 50)->default('MENUNGGU_PERSETUJUAN')->change();

                if (!Schema::hasColumn('penempatan_pkl', 'catatan_guru')) {
                    $table->text('catatan_guru')->nullable()->after('status');
                }
                if (!Schema::hasColumn('penempatan_pkl', 'disetujui_oleh')) {
                    $table->string('disetujui_oleh', 64)->nullable()->after('catatan_guru');
                }
                if (!Schema::hasColumn('penempatan_pkl', 'disetujui_pada')) {
                    $table->timestamp('disetujui_pada')->nullable()->after('disetujui_oleh');
                }
                if (!Schema::hasColumn('penempatan_pkl', 'pengajuan_mandiri')) {
                    $table->boolean('pengajuan_mandiri')->default(false)->after('disetujui_pada');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cv_resumes')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $columns = [
                    'file_path', 'file_name', 'file_size', 'file_mime',
                    'catatan_guru', 'status_review_guru', 'reviewed_by_guru_id', 'reviewed_at'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('cv_resumes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('penempatan_pkl')) {
            Schema::table('penempatan_pkl', function (Blueprint $table) {
                $columns = ['catatan_guru', 'disetujui_oleh', 'disetujui_pada', 'pengajuan_mandiri'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('penempatan_pkl', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
