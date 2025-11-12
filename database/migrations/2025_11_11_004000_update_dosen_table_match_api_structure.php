<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update dosen table structure to match GetListDosen API response:
     * - Rename: nama → nama_dosen
     * - Rename: feeder_id → id_dosen (match API field name exactly)
     */
    public function up(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            // Rename nama to nama_dosen to match API
            $table->renameColumn('nama', 'nama_dosen');

            // Rename dosen_feeder_id to id_dosen to match API exactly
            $table->renameColumn('dosen_feeder_id', 'id_dosen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            // Rename back to old names
            $table->renameColumn('nama_dosen', 'nama');
            $table->renameColumn('id_dosen', 'dosen_feeder_id');
        });
    }
};
