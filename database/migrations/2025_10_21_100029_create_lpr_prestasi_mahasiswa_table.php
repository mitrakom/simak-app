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
        Schema::create('lpr_prestasi_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->uuid('mahasiswa_feeder_id')->index();
            $table->uuid('prestasi_feeder_id')->index(); // ID Prestasi dari Feeder
            $table->string('nim', 20);
            $table->string('nama_mahasiswa', 100);
            $table->string('nama_prestasi', 160);
            $table->string('tingkat_prestasi', 50)->nullable();
            $table->year('tahun_prestasi');
            $table->string('penyelenggara', 100)->nullable();
            $table->timestamps();

            // Unique constraint berdasarkan institusi dan ID prestasi dari Feeder
            $table->unique(['institusi_id', 'prestasi_feeder_id'], 'lpr_prestasi_mhs_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_prestasi_mahasiswa');
    }
};
