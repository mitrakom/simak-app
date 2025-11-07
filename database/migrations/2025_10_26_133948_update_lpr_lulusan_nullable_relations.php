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
        Schema::table('lpr_lulusan', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['mahasiswa_id']);
            $table->dropForeign(['prodi_id']);

            // Modify columns to be nullable
            $table->foreignId('mahasiswa_id')->nullable()->change();
            $table->foreignId('prodi_id')->nullable()->change();

            // Re-add foreign key constraints with nullable support
            $table->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->onDelete('set null');
            $table->foreign('prodi_id')->references('id')->on('prodis')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_lulusan', function (Blueprint $table) {
            // Drop nullable foreign key constraints
            $table->dropForeign(['mahasiswa_id']);
            $table->dropForeign(['prodi_id']);

            // Change back to non-nullable (this might fail if there are null values)
            $table->foreignId('mahasiswa_id')->nullable(false)->change();
            $table->foreignId('prodi_id')->nullable(false)->change();

            // Re-add original foreign key constraints
            $table->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->onDelete('cascade');
            $table->foreign('prodi_id')->references('id')->on('prodis')->onDelete('cascade');
        });
    }
};
