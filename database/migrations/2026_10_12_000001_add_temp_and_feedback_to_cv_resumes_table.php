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
        Schema::table('cv_resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('cv_resumes', 'temp')) {
                $table->boolean('temp')->default(false)->index()->after('is_main');
            }
            if (!Schema::hasColumn('cv_resumes', 'feedback')) {
                $table->json('feedback')->nullable()->after('temp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_resumes', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('cv_resumes', 'feedback')) {
                $columns[] = 'feedback';
            }
            if (Schema::hasColumn('cv_resumes', 'temp')) {
                $columns[] = 'temp';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
