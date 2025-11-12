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
        // Rename column directly - MySQL will handle the unique constraint automatically
        Schema::table('dosen', function (Blueprint $table) {
            $table->renameColumn('feeder_id', 'dosen_feeder_id');
        });

        // Update column comment
        Schema::table('dosen', function (Blueprint $table) {
            $table->uuid('dosen_feeder_id')->comment('ID Dosen dari Feeder (id_dosen)')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back to original
        Schema::table('dosen', function (Blueprint $table) {
            $table->renameColumn('dosen_feeder_id', 'feeder_id');
        });
    }
};
