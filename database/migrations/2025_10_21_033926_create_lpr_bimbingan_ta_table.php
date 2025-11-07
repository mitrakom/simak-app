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
        Schema::create('lpr_bimbingan_ta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();

            // --- Feeder IDs ---
            $table->uuid('bimbingan_feeder_id')->index();        // ID Bimbingan dari Feeder (id_bimbing_mahasiswa) - Kunci Unik Feeder
            $table->uuid('aktivitas_feeder_id')->index();        // ID Aktivitas Mahasiswa (Skripsi/Tesis) dari Feeder
            $table->uuid('mahasiswa_feeder_id')->index();        // ID Mahasiswa (Orang) dari Feeder
            $table->uuid('dosen_feeder_id')->index();            // ID Dosen dari Feeder

            // --- Denormalisasi Data Mahasiswa ---
            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);

            // --- Denormalisasi Data Dosen ---
            $table->string('nidn', 10)->nullable(); // NIDN bisa null
            $table->string('nama_dosen', 100);

            // --- Detail Bimbingan ---
            $table->unsignedTinyInteger('posisi_pembimbing'); // 1 atau 2
            $table->string('judul_ta', 500)->nullable();    // Denormalisasi judul dari id_aktivitas

            $table->timestamps();
            // --- Unique Constraint ---
            // Unik berdasarkan institusi dan ID bimbingan dari Feeder
            $table->unique(['institusi_id', 'bimbingan_feeder_id'], 'lpr_bimbingan_ta_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_bimbingan_ta');
    }
};
