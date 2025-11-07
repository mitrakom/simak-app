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
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->onDelete('cascade');
            $table->foreignId('prodi_id')->constrained('prodis')->onDelete('cascade');

            // Identifier unik INDIVIDU dari API Feeder
            $table->uuid('feeder_id')->index(); // ID Mahasiswa dari Feeder (id_mahasiswa)

            $table->string('nim', 20);
            $table->string('nama', 100);
            $table->year('angkatan'); // Angkatan pertama masuk PT ini?

            // Tambahkan kolom biodata dasar jika diperlukan (opsional, bisa diambil dari API saat detail)
            // $table->string('jenis_kelamin', 1)->nullable();
            // $table->date('tanggal_lahir')->nullable();
            // $table->string('nama_ibu_kandung', 100)->nullable(); // Penting untuk verifikasi

            $table->timestamps();

            // Constraint unik NIM per institusi
            $table->unique(['institusi_id', 'nim'], 'mahasiswa_nim_unique');

            // Constraint unik Feeder ID per institusi (mencegah duplikasi orang)
            $table->unique(['institusi_id', 'feeder_id'], 'mahasiswa_feeder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
