<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cv_resumes')) {
            return;
        }

        // 1. Pastikan kolom user_id tersedia
        if (Schema::hasColumn('cv_resumes', 'siswa_id') && !Schema::hasColumn('cv_resumes', 'user_id')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->renameColumn('siswa_id', 'user_id');
            });
        } elseif (!Schema::hasColumn('cv_resumes', 'user_id')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->string('user_id', 64)->nullable()->index();
            });
        }

        // 2. Pastikan foreign key user_id ke profil_siswa.user_id ada jika belum dibuat
        $fkExists = false;
        try {
            $fkExists = DB::table('information_schema.table_constraints')
                ->where('table_name', 'cv_resumes')
                ->where('constraint_type', 'FOREIGN KEY')
                ->where('constraint_name', 'cv_resumes_user_id_foreign')
                ->exists();
        } catch (\Throwable) {}

        if (!$fkExists && Schema::hasTable('profil_siswa') && Schema::hasColumn('cv_resumes', 'user_id')) {
            try {
                Schema::table('cv_resumes', function (Blueprint $table) {
                    $table->foreign('user_id')->references('user_id')->on('profil_siswa')->cascadeOnDelete();
                });
            } catch (\Throwable) {}
        }

        // 3. Pastikan kolom raw_markdown tersedia
        if (Schema::hasColumn('cv_resumes', 'konten_markdown') && !Schema::hasColumn('cv_resumes', 'raw_markdown')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->renameColumn('konten_markdown', 'raw_markdown');
            });
        } elseif (!Schema::hasColumn('cv_resumes', 'raw_markdown')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->longText('raw_markdown')->nullable();
            });
        }

        // 4. Pastikan kolom is_main tersedia
        if (Schema::hasColumn('cv_resumes', 'is_primary') && !Schema::hasColumn('cv_resumes', 'is_main')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->renameColumn('is_primary', 'is_main');
            });
        } elseif (!Schema::hasColumn('cv_resumes', 'is_main')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->boolean('is_main')->default(true)->index();
            });
        }

        // 5. Pastikan kolom checksum tersedia dan terisi
        if (!Schema::hasColumn('cv_resumes', 'checksum')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->string('checksum', 64)->nullable()->index();
            });

            DB::table('cv_resumes')->get()->each(function ($row) {
                $content = $row->raw_markdown ?? $row->konten_markdown ?? '';
                $normalized = trim(str_replace("\r\n", "\n", (string) $content));
                $hash = hash('sha256', $normalized);
                DB::table('cv_resumes')->where('id', $row->id)->update([
                    'checksum' => $hash,
                ]);
            });
        }

        // 6. Pastikan kolom temp, feedback, score tersedia
        if (!Schema::hasColumn('cv_resumes', 'temp')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->boolean('temp')->default(false)->index();
            });
        }

        if (!Schema::hasColumn('cv_resumes', 'feedback')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->json('feedback')->nullable();
            });
        }

        if (!Schema::hasColumn('cv_resumes', 'score')) {
            Schema::table('cv_resumes', function (Blueprint $table) {
                $table->integer('score')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for safety of existing data
    }
};
