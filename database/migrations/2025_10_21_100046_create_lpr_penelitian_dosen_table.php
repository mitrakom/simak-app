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
        Schema::create('lpr_penelitian_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->uuid('dosen_feeder_id')->index();
            $table->uuid('penelitian_feeder_id')->index(); // ID Penelitian dari Feeder
            $table->string('nidn', 10);
            $table->string('nama_dosen', 100);
            $table->string('judul_penelitian', 500);
            $table->string('tahun_kegiatan', 10);
            $table->string('nama_lembaga_iptek', 100)->nullable();
            $table->timestamps();
            // Unique constraint berdasarkan institusi, ID penelitian, dan ID dosen
            $table->unique(['institusi_id', 'penelitian_feeder_id', 'dosen_feeder_id'], 'lpr_penelitian_dosen_unique'); // Beri nama constraint agar mudah diidentifikasi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lpr_penelitian_dosen');
    }
};
