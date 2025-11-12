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
        // Make this migration idempotent and safe depending on current schema.
        // If `feeder_id` exists, perform rename; if `id_prodi` already exists
        // skip the rename and only ensure other columns/types are adjusted.

        $hasFeeder = Schema::hasColumn('prodis', 'feeder_id');
        $hasIdProdi = Schema::hasColumn('prodis', 'id_prodi');

        // Drop kode unique if exists
        $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodis'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);

        Schema::table('prodis', function (Blueprint $table) use ($hasFeeder, $hasIdProdi, $indexNames) {
            if (in_array('prodis_kode_unique', $indexNames)) {
                $table->dropUnique('prodis_kode_unique');
            }

            if ($hasFeeder && ! $hasIdProdi) {
                // Classic case: feeder_id exists and must be renamed
                $table->renameColumn('feeder_id', 'id_prodi');
            }

            // Rename existing columns to match API if not already renamed
            if (! Schema::hasColumn('prodis', 'kode_program_studi')) {
                $table->renameColumn('kode', 'kode_program_studi');
            }
            if (! Schema::hasColumn('prodis', 'nama_program_studi')) {
                $table->renameColumn('nama', 'nama_program_studi');
            }
            if (! Schema::hasColumn('prodis', 'nama_jenjang_pendidikan')) {
                $table->renameColumn('jenjang', 'nama_jenjang_pendidikan');
            }

            // Add new column from API if missing
            if (! Schema::hasColumn('prodis', 'id_jenjang_pendidikan')) {
                $table->string('id_jenjang_pendidikan', 10)->nullable()->after('status');
            }

            // Update column types to match API
            $table->string('status', 10)->nullable()->change(); // Allow longer status codes
            $table->string('kode_program_studi', 20)->change(); // Allow longer codes
            $table->string('nama_program_studi', 200)->change(); // Allow longer names
            $table->string('nama_jenjang_pendidikan', 50)->change(); // Allow longer jenjang
        });

        // Recreate unique constraints with new column names if missing
        if (! in_array('prodis_kode_unique', $indexNames)) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'kode_program_studi'], 'prodis_kode_unique');
            });
        }
        if (! in_array('prodis_id_prodi_unique', $indexNames) && Schema::hasColumn('prodis', 'id_prodi')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'id_prodi'], 'prodis_id_prodi_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make down() safe: only reverse operations that exist in the schema.
        $indexRows = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodis'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexRows);

        Schema::table('prodis', function (Blueprint $table) use ($indexNames) {
            if (in_array('prodis_kode_unique', $indexNames)) {
                $table->dropUnique('prodis_kode_unique');
            }
            if (in_array('prodis_id_prodi_unique', $indexNames)) {
                $table->dropUnique('prodis_id_prodi_unique');
            }
        });

        Schema::table('prodis', function (Blueprint $table) {
            if (Schema::hasColumn('prodis', 'id_jenjang_pendidikan')) {
                $table->dropColumn('id_jenjang_pendidikan');
            }

            if (Schema::hasColumn('prodis', 'id_prodi') && ! Schema::hasColumn('prodis', 'feeder_id')) {
                // Try to rename id_prodi back to feeder_id if feeder_id absent
                $table->renameColumn('id_prodi', 'feeder_id');
            }

            // Rename back other columns if necessary
            if (Schema::hasColumn('prodis', 'kode_program_studi')) {
                $table->renameColumn('kode_program_studi', 'kode');
            }
            if (Schema::hasColumn('prodis', 'nama_program_studi')) {
                $table->renameColumn('nama_program_studi', 'nama');
            }
            if (Schema::hasColumn('prodis', 'nama_jenjang_pendidikan')) {
                $table->renameColumn('nama_jenjang_pendidikan', 'jenjang');
            }

            // Restore old column types where appropriate
            $table->string('status', 1)->nullable()->change();
            $table->string('kode', 10)->change();
            $table->string('nama', 100)->change();
            $table->string('jenjang', 10)->change();
        });

        // Recreate old unique constraints if missing
        if (! in_array('prodis_kode_unique', $indexNames)) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'kode'], 'prodis_kode_unique');
            });
        }
        if (! in_array('prodis_feeder_unique', $indexNames) && Schema::hasColumn('prodis', 'feeder_id')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->unique(['institusi_id', 'feeder_id'], 'prodis_feeder_unique');
            });
        }
    }
};
