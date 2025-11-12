<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FIX CRITICAL BUG: Mahasiswa unique constraint harus menggunakan registrasi_feeder_id, bukan mahasiswa_feeder_id
     *
     * BACKGROUND:
     * - id_mahasiswa (mahasiswa_feeder_id) = identifier INDIVIDU/ORANG
     * - id_registrasi_mahasiswa (registrasi_feeder_id) = identifier ENROLLMENT ke prodi
     *
     * PROBLEM:
     * - Satu orang bisa kuliah di 2+ prodi secara bersamaan (double degree)
     * - Jika unique constraint pakai mahasiswa_feeder_id, enrollment ke-2 akan GAGAL (duplicate key error)
     *
     * SOLUTION:
     * - Unique constraint harus pakai registrasi_feeder_id (enrollment identifier)
     * - Setiap enrollment adalah record terpisah meskipun orang yang sama
     *
     * @see docs/architecture/MAHASISWA_DUAL_IDENTIFIER.md
     */
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Create unique constraint on the CORRECT identifier: registrasi_feeder_id
            // This allows one person (mahasiswa_feeder_id) to have multiple enrollments (registrasi_feeder_id)
            $table->unique(['institusi_id', 'registrasi_feeder_id'], 'mahasiswa_registrasi_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropUnique('mahasiswa_registrasi_unique');
        });
    }
};
