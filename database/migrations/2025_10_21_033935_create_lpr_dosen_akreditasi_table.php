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
        Schema::create('lpr_dosen_akreditasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->uuid('dosen_feeder_id')->index();
            // Kunci Unik: Satu rekap per dosen internal per institusi
            $table->foreignId('dosen_id')->unique()->constrained('dosen')->cascadeOnDelete();
            // Denormalisasi data dosen
            $table->string('nidn', 10);
            $table->string('nama_dosen', 100);
            // Kolom rekapitulasi dari API Riwayat
            $table->string('pendidikan_tertinggi', 20)->nullable();
            $table->string('jabatan_fungsional', 50)->nullable();
            $table->boolean('sudah_sertifikasi')->default(false);
            $table->timestamps();
            $table->unique(['institusi_id', 'dosen_feeder_id'], 'lpr_dosen_feeder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_dosen_akreditasi');
    }
};
