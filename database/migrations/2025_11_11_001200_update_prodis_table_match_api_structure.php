<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update prodis table structure to match GetProdi API response:
     * - id_prodi (UUID)
     * - kode_program_studi (string)
     * - nama_program_studi (string)
     * - status (string)
     * - id_jenjang_pendidikan (string)
     * - nama_jenjang_pendidikan (string)
     */
    public function up(): void
    {
        Schema::table('prodis', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('prodis_kode_unique');

            // Rename feeder_id to id_prodi (match API response)
            $table->renameColumn('feeder_id', 'id_prodi');

            // Rename existing columns to match API
            $table->renameColumn('kode', 'kode_program_studi');
            $table->renameColumn('nama', 'nama_program_studi');
            $table->renameColumn('jenjang', 'nama_jenjang_pendidikan');

            // Add new column from API
            $table->string('id_jenjang_pendidikan', 10)->nullable()->after('status');

            // Update column types to match API
            $table->string('status', 10)->nullable()->change(); // Allow longer status codes
            $table->string('kode_program_studi', 20)->change(); // Allow longer codes
            $table->string('nama_program_studi', 200)->change(); // Allow longer names
            $table->string('nama_jenjang_pendidikan', 50)->change(); // Allow longer jenjang
        });

        // Recreate unique constraints with new column names
        Schema::table('prodis', function (Blueprint $table) {
            $table->unique(['institusi_id', 'kode_program_studi'], 'prodis_kode_unique');
            $table->unique(['institusi_id', 'id_prodi'], 'prodis_id_prodi_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prodis', function (Blueprint $table) {
            // Drop new unique constraints
            $table->dropUnique('prodis_kode_unique');
            $table->dropUnique('prodis_id_prodi_unique');
        });

        Schema::table('prodis', function (Blueprint $table) {
            // Remove new column
            $table->dropColumn('id_jenjang_pendidikan');

            // Rename back to old names
            $table->renameColumn('id_prodi', 'feeder_id');
            $table->renameColumn('kode_program_studi', 'kode');
            $table->renameColumn('nama_program_studi', 'nama');
            $table->renameColumn('nama_jenjang_pendidikan', 'jenjang');

            // Restore old column types
            $table->string('status', 1)->nullable()->change();
            $table->string('kode', 10)->change();
            $table->string('nama', 100)->change();
            $table->string('jenjang', 10)->change();
        });

        // Recreate old unique constraints
        Schema::table('prodis', function (Blueprint $table) {
            $table->unique(['institusi_id', 'kode'], 'prodis_kode_unique');
            $table->unique(['institusi_id', 'feeder_id'], 'prodis_feeder_unique');
        });
    }
};
