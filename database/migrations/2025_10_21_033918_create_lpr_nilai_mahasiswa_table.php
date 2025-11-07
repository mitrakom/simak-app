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
        Schema::create('lpr_nilai_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();

            // --- Feeder IDs ---
            $table->uuid('mahasiswa_feeder_id')->index();      // ID Mahasiswa (Orang) dari Feeder
            $table->uuid('registrasi_feeder_id')->index();     // ID Registrasi dari Feeder (id_registrasi_mahasiswa)
            $table->uuid('matkul_feeder_id')->index();         // ID Mata Kuliah dari Feeder (id_matkul)

            // --- Denormalisasi Data ---
            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);
            $table->year('angkatan')->nullable(); // Denormalisasi angkatan
            $table->string('kode_mk', 20)->index();
            $table->string('nama_mk', 100);
            $table->decimal('sks_mk', 4, 2); // Menggunakan decimal untuk SKS (misal: 3.00)

            // --- Detail Nilai ---
            $table->string('semester', 5)->index(); // Menyimpan id_periode
            $table->decimal('nilai_angka', 5, 2)->nullable(); // Menyimpan nilai_angka (precision disesuaikan)
            $table->string('nilai_huruf', 3);
            $table->decimal('nilai_indeks', 3, 2); // Menyimpan nilai_indeks

            $table->timestamps();

            // --- Unique Constraint ---
            // Unik berdasarkan institusi, registrasi, matkul, dan semester
            $table->unique(
                ['institusi_id', 'registrasi_feeder_id', 'matkul_feeder_id', 'semester'],
                'lpr_nilai_mahasiswa_unique' // Memberi nama constraint
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_nilai_mahasiswa');
    }
};
