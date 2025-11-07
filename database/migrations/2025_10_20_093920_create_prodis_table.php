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
        Schema::create('prodis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained('institusis')->onDelete('cascade');

            // Identifier unik dari API Feeder
            $table->uuid('feeder_id')->index(); // ID Prodi dari Feeder (id_prodi)

            // Data Prodi
            $table->string('nama', 100);
            $table->string('kode', 10);
            $table->string('jenjang', 10);
            $table->string('status', 1)->nullable(); // Menambahkan status prodi

            $table->timestamps();

            // Constraint unik Kode Prodi per institusi
            $table->unique(['institusi_id', 'kode'], 'prodis_kode_unique');

            // Constraint unik Feeder ID per institusi (mencegah duplikasi prodi dari feeder)
            $table->unique(['institusi_id', 'feeder_id'], 'prodis_feeder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodis');
    }
};
