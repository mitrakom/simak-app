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
        Schema::create('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();

            // $table->uuid('mahasiswa_feeder_id')->index();
            // $table->uuid('aktivitas_feeder_id')->index(); // ID Aktivitas dari Feeder
            // --- Feeder IDs ---
            $table->uuid('anggota_aktivitas_feeder_id')->index(); // ID Anggota dari GetListAnggotaAktivitasMahasiswa (id_anggota) - Kunci Unik Feeder
            $table->uuid('aktivitas_feeder_id')->index();       // ID Aktivitas dari Feeder (id_aktivitas)
            $table->uuid('mahasiswa_feeder_id')->index();        // ID Mahasiswa (orang) dari Feeder (id_mahasiswa, perlu diambil saat sync)
            $table->uuid('registrasi_feeder_id')->index();     // ID Registrasi Mhs dari Feeder (id_registrasi_mahasiswa)

            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);
            $table->string('judul_aktivitas', 500);
            $table->string('jenis_aktivitas', 50);
            $table->string('lokasi', 100)->nullable();
            $table->boolean('apakah_mbkm')->default(false);
            $table->string('semester', 5)->nullable();          // Semester aktivitas berlangsung, sumber id_semester dari Feeder
            // --- Data Keanggotaan (dari GetListAnggotaAktivitasMahasiswa) ---
            $table->string('peran_mahasiswa', 20)->nullable(); // Misal: Personal, Ketua, Anggota

            $table->timestamps();

            // --- Unique Constraint ---
            // Unik berdasarkan institusi dan ID keanggotaan dari Feeder
            $table->unique(['institusi_id', 'anggota_aktivitas_feeder_id'], 'lpr_aktivitas_mhs_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_aktivitas_mahasiswa');
    }
};
