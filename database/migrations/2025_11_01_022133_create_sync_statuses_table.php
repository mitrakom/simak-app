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
        Schema::create('sync_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institusi_id')->index(); // Foreign key ref
            $table->string('sync_type')->index(); // dosen, mahasiswa, prodi, nilai_mahasiswa, etc
            $table->enum('status', ['tersinkronisasi', 'error', 'pending', 'sinkronisasi', 'memulai'])->default('pending')->index();
            $table->integer('total_records')->default(0);
            $table->integer('current_progress')->default(0);
            $table->text('progress_message')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('last_sync_time')->nullable()->index();
            $table->string('sync_process_id')->nullable()->unique();
            $table->timestamps();

            // Composite index untuk query yang sering digunakan
            $table->index(['institusi_id', 'sync_type']);
            $table->index(['institusi_id', 'status']);

            // Foreign key constraint - add later after institusi table check
            // $table->foreign('institusi_id')->references('id')->on('institusi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_statuses');
    }
};
