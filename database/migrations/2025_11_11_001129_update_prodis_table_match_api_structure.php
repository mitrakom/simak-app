<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // Safer approach: add new nullable column `id_prodi`, copy values from
        // existing `prodi_feeder_id` into it, create the new unique index, then
        // drop the old unique index and old column. This avoids dropping an
        // index that may be required by a foreign key constraint.

        // Check what indexes already exist
        $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodis'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);

        // Add id_prodi column if not exists
        if (! Schema::hasColumn('prodis', 'id_prodi')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->uuid('id_prodi')->nullable()->after('prodi_feeder_id');
            });

            // Copy data from prodi_feeder_id -> id_prodi
            DB::statement('UPDATE prodis SET id_prodi = prodi_feeder_id');
        }

        // Create unique index on id_prodi if not exists
        if (! in_array('prodis_id_prodi_unique', $indexNames)) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'id_prodi'], 'prodis_id_prodi_unique');
            });
        }

        Schema::table('prodis', function (Blueprint $table) {
            // Rename columns to match API (only if not already renamed)
            if (Schema::hasColumn('prodis', 'kode') && ! Schema::hasColumn('prodis', 'kode_program_studi')) {
                $table->renameColumn('kode', 'kode_program_studi');
            }
            if (Schema::hasColumn('prodis', 'nama') && ! Schema::hasColumn('prodis', 'nama_program_studi')) {
                $table->renameColumn('nama', 'nama_program_studi');
            }
            if (Schema::hasColumn('prodis', 'jenjang') && ! Schema::hasColumn('prodis', 'nama_jenjang_pendidikan')) {
                $table->renameColumn('jenjang', 'nama_jenjang_pendidikan');
            }

            // Add id_jenjang_pendidikan if not exists
            if (! Schema::hasColumn('prodis', 'id_jenjang_pendidikan')) {
                $table->string('id_jenjang_pendidikan', 10)->nullable()->after('status');
            }

            // Update column types to match API
            $table->string('status', 10)->nullable()->change();
            $table->string('kode_program_studi', 20)->change();
            $table->string('nama_program_studi', 200)->change();
            $table->string('nama_jenjang_pendidikan', 50)->change();
        });

        // Note: we intentionally keep the old `prodi_feeder_id` column and its
        // `prodis_feeder_unique` constraint in place to avoid causing
        // foreign-key related errors in other tables. The new `id_prodi`
        // column contains the same values and has its own unique index
        // (`prodis_id_prodi_unique`). Dropping the old column/index should be
        // performed later once dependent foreign keys are updated.

        // Refresh index list after potential column renames
        $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodis'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);

        // Create kode unique index only if it doesn't exist
        if (! in_array('prodis_kode_unique', $indexNames)) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'kode_program_studi'], 'prodis_kode_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the unique created on id_prodi and remove the column added in up()
        Schema::table('prodis', function (Blueprint $table) {
            $table->dropUnique('prodis_id_prodi_unique');
        });

        Schema::table('prodis', function (Blueprint $table) {
            // Remove new column from API
            $table->dropColumn('id_jenjang_pendidikan');
            $table->dropColumn('id_prodi');

            // Rename back to old names
            $table->renameColumn('kode_program_studi', 'kode');
            $table->renameColumn('nama_program_studi', 'nama');
            $table->renameColumn('nama_jenjang_pendidikan', 'jenjang');

            // Restore old column types
            $table->string('status', 1)->nullable()->change();
            $table->string('kode', 10)->change();
            $table->string('nama', 100)->change();
            $table->string('jenjang', 10)->change();
        });

        // Ensure kode unique is restored (the prodis_feeder_unique is left as-is)
        Schema::table('prodis', function (Blueprint $table) {
            $table->unique(['institusi_id', 'kode'], 'prodis_kode_unique');
        });
    }
};
