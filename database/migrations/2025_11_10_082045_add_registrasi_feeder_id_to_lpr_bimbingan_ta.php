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
        Schema::table('lpr_bimbingan_ta', function (Blueprint $table) {
            // Add registrasi_feeder_id column (nullable - optional field)
            // Note: lpr_bimbingan_ta table structure varies between migrations
            // So we add without specifying position (after)
            $table->uuid('registrasi_feeder_id')
                ->nullable()
                ->comment('ID Registrasi Mahasiswa dari Feeder (id_registrasi_mahasiswa) - Optional');

            $table->index('registrasi_feeder_id');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_bimbingan_ta', function (Blueprint $table) {
            $table->dropColumn('registrasi_feeder_id');
        });
    }
};
