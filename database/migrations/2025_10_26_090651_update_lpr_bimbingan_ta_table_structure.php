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
        // Drop table and recreate with correct structure
        Schema::dropIfExists('lpr_bimbingan_ta');

        Schema::create('lpr_bimbingan_ta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->nullable()->constrained('mahasiswa')->nullOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();

            // --- Fields sesuai dokumentasi API GetMahasiswaBimbinganDosen ---
            $table->uuid('id_bimbing_mahasiswa')->index(); // Primary key dari API
            $table->uuid('id_aktivitas');                  // ID aktivitas
            $table->string('judul', 500)->nullable();      // Judul TA/Skripsi
            $table->integer('id_kategori_kegiatan')->nullable();
            $table->string('nama_kategori_kegiatan', 300)->nullable();
            $table->uuid('id_dosen');                      // ID dosen dari API
            $table->string('nidn', 10)->nullable();        // NIDN dosen
            $table->string('nuptk', 20)->nullable();       // NUPTK dosen
            $table->string('nama_dosen', 200);             // Nama dosen
            $table->string('pembimbing_ke', 10);           // Urutan pembimbing (1, 2, dst)

            $table->timestamps();

            // Unique constraint berdasarkan institusi dan ID bimbingan dari Feeder
            $table->unique(['institusi_id', 'id_bimbing_mahasiswa'], 'lpr_bimbingan_ta_unique');

            // Index untuk pencarian
            $table->index(['institusi_id', 'id_aktivitas']);
            $table->index(['institusi_id', 'id_dosen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the recreated table
        Schema::dropIfExists('lpr_bimbingan_ta');

        // Recreate with original structure
        Schema::create('lpr_bimbingan_ta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();

            // --- Feeder IDs ---
            $table->uuid('bimbingan_feeder_id')->index();
            $table->uuid('aktivitas_feeder_id')->index();
            $table->uuid('mahasiswa_feeder_id')->index();
            $table->uuid('dosen_feeder_id')->index();

            // --- Denormalisasi Data Mahasiswa ---
            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);

            // --- Denormalisasi Data Dosen ---
            $table->string('nidn', 10)->nullable();
            $table->string('nama_dosen', 100);

            // --- Detail Bimbingan ---
            $table->unsignedTinyInteger('posisi_pembimbing');
            $table->string('judul_ta', 500)->nullable();

            $table->timestamps();
            $table->unique(['institusi_id', 'bimbingan_feeder_id'], 'lpr_bimbingan_ta_unique');
        });
    }
};
