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
        Schema::create('lpr_lulusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('prodis')->cascadeOnDelete();

            $table->uuid('mahasiswa_feeder_id')->index();
            $table->uuid('registrasi_feeder_id')->index();     // ID Registrasi dari Feeder (id_registrasi_mahasiswa) - Kunci Unik Feeder

            // --- Denormalisasi Data ---
            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);
            $table->string('nama_prodi', 100);
            $table->year('angkatan')->nullable(); // Menambahkan Angkatan (dari response API)

            // --- Detail Kelulusan/DO (dari API) ---
            $table->string('status_keluar', 25);         // Menyimpan nama_jenis_keluar
            $table->date('tanggal_keluar')->nullable(); // Menyimpan tanggal_keluar
            $table->decimal('ipk_lulusan', 4, 2)->nullable(); // IPK bisa > 3.00, jadi (4,2) lebih aman
            $table->unsignedSmallInteger('masa_studi_bulan')->nullable(); // Dihitung dari tgl_masuk_sp & tgl_keluar
            $table->string('nomor_ijazah', 80)->nullable();
            $table->string('nomor_sk_yudisium', 80)->nullable();
            $table->date('tanggal_sk_yudisium')->nullable();
            $table->string('judul_skripsi', 500)->nullable();
            $table->string('periode_keluar', 5)->nullable(); // Menyimpan id_periode_keluar

            $table->timestamps();

            // --- Unique Constraint dengan nama yang lebih pendek ---
            // Unik berdasarkan institusi dan ID Registrasi dari Feeder
            $table->unique(['institusi_id', 'registrasi_feeder_id'], 'lpr_lulusan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_lulusan');
    }
};
