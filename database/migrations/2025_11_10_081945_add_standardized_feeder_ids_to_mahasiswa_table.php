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
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Step 1: Add new registrasi_feeder_id column (nullable initially, will be populated then made NOT NULL)
            $table->uuid('registrasi_feeder_id')->nullable()->after('feeder_id')->comment('ID Registrasi Mahasiswa dari Feeder (id_registrasi_mahasiswa) - PRIMARY identifier');
            $table->index('registrasi_feeder_id');

            // Step 2: Drop old unique constraint before renaming column
            $table->dropUnique('mahasiswa_feeder_unique');
        });

        // Step 3: Rename column (separate statement for better compatibility)
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->renameColumn('feeder_id', 'mahasiswa_feeder_id');
        });

        // Step 4: Update column comment after rename
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->uuid('mahasiswa_feeder_id')->comment('ID Mahasiswa dari Feeder (id_mahasiswa) - For biodata/person')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Reverse: Rename back to feeder_id
            $table->renameColumn('mahasiswa_feeder_id', 'feeder_id');

            // Remove registrasi_feeder_id column
            $table->dropColumn('registrasi_feeder_id');

            // Restore original unique constraint
            $table->unique(['institusi_id', 'feeder_id'], 'mahasiswa_feeder_unique');
        });
    }
};
