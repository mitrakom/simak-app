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
        Schema::table('lpr_prestasi_mahasiswa', function (Blueprint $table) {
            // Add registrasi_feeder_id column (nullable - optional field)
            $table->uuid('registrasi_feeder_id')
                ->nullable()
                ->after('mahasiswa_feeder_id')
                ->comment('ID Registrasi Mahasiswa dari Feeder (id_registrasi_mahasiswa) - Optional');

            $table->index('registrasi_feeder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_prestasi_mahasiswa', function (Blueprint $table) {
            $table->dropColumn('registrasi_feeder_id');
        });
    }
};
