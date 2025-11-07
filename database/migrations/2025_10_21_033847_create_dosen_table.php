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
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained()->onDelete('cascade')->index();

            // Identifier unik INDIVIDU dari API Feeder
            $table->uuid('feeder_id')->index(); // ID Dosen dari Feeder (id_dosen)

            // Data dari API GetListDosen
            $table->string('nidn', 10)->nullable()->index(); // NIDN bisa jadi null, tapi tetap diindeks
            $table->string('nuptk', 20)->nullable(); // NUPTK
            $table->string('nip', 30)->nullable(); // NIP
            $table->string('nama', 100); // nama_dosen
            $table->char('jenis_kelamin', 1)->nullable(); // L/P
            $table->integer('id_agama')->nullable(); // ID Agama
            $table->string('nama_agama', 50)->nullable(); // Nama Agama
            $table->date('tanggal_lahir')->nullable(); // Tanggal Lahir
            $table->string('id_status_aktif', 10)->nullable(); // ID Status Aktif
            $table->string('nama_status_aktif', 50)->nullable(); // Nama Status Aktif

            $table->timestamps();

            // Constraint unik Feeder ID per institusi (mencegah duplikasi orang)
            $table->unique(['institusi_id', 'feeder_id'], 'dosen_feeder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
