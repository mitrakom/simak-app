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
        Schema::create('lpr_akademik_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->onDelete('cascade');
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->onDelete('cascade');
            // --- Feeder IDs ---
            $table->uuid('mahasiswa_feeder_id')->index();      // ID Mahasiswa (Orang) dari Feeder
            $table->uuid('registrasi_feeder_id')->index();     // ID Registrasi dari Feeder (id_registrasi_mahasiswa)

            // --- Denormalisasi Data ---
            $table->string('nim', 20)->index();
            $table->string('nama_mahasiswa', 100);
            $table->year('angkatan');
            $table->string('nama_prodi', 100); // Nama Prodi untuk kemudahan report

            // --- Data Akademik per Semester ---
            $table->string('semester', 5)->index(); // Menyimpan id_semester
            $table->decimal('ips', 3, 2)->default(0);
            $table->decimal('ipk', 3, 2)->default(0);
            $table->unsignedSmallInteger('sks_semester')->default(0); // SKS Semester (bisa > 255)
            $table->unsignedSmallInteger('sks_total')->default(0);    // SKS Total (bisa > 255)
            $table->string('status_mahasiswa', 20); // Menyimpan nama_status_mahasiswa

            $table->timestamps();

            // --- Unique Constraint dengan nama yang lebih pendek ---
            // Unik berdasarkan institusi, registrasi, dan semester
            $table->unique(['institusi_id', 'registrasi_feeder_id', 'semester'], 'lpr_akademik_mhs_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_akademik_mahasiswa');
    }
};
