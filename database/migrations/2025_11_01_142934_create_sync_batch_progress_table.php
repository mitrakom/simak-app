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
        Schema::create('sync_batch_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institusi_id')->index(); // Multi-tenancy support
            $table->string('batch_id')->unique()->index(); // Laravel job batch ID
            $table->string('sync_type')->index(); // dosen, mahasiswa, prodi, etc
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending')->index();

            // Progress tracking
            $table->integer('total_records')->default(0); // Total records to process
            $table->integer('processed_records')->default(0); // Successfully processed
            $table->integer('failed_records')->default(0); // Failed records
            $table->integer('progress_percentage')->default(0); // 0-100

            // Metadata
            $table->text('error_message')->nullable(); // Error details jika gagal
            $table->json('summary')->nullable(); // JSON data: created_count, updated_count, skipped_count
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes untuk query yang sering digunakan
            $table->index(['institusi_id', 'sync_type']);
            $table->index(['institusi_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_batch_progress');
    }
};
