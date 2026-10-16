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
        Schema::create('cv_resumes', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 64)->index();
            $table->foreign('user_id')->references('user_id')->on('profil_siswa')->cascadeOnDelete();
            $table->longText('raw_markdown');
            $table->string('checksum', 64)->index();
            $table->boolean('is_main')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_resumes');
    }
};
