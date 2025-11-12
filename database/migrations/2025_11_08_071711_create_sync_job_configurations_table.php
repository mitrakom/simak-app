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
        Schema::create('sync_job_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institusi_id')->constrained()->cascadeOnDelete();
            $table->string('job_class'); // Class name of the job
            $table->string('job_name'); // Display name
            $table->text('description')->nullable();
            $table->json('default_parameters')->nullable(); // Default parameters untuk job
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Urutan tampilan
            $table->string('category')->default('general'); // Kategori: mahasiswa, dosen, akademik, dll
            $table->timestamps();

            $table->unique(['institusi_id', 'job_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_job_configurations');
    }
};
