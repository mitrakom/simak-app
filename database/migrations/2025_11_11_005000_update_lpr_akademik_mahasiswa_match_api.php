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
     * Update lpr_akademik_mahasiswa table to match GetListPerkuliahanMahasiswa API:
     * - Rename: semester → id_semester
     * - Rename: status_mahasiswa → nama_status_mahasiswa
     * - Rename: nama_prodi → nama_program_studi
     * - Add: nama_semester, id_status_mahasiswa, biaya_kuliah_smt, id_pembiayaan
     */
    public function up(): void
    {
        // Safer approach: add new column id_semester, copy values from semester,
        // create new unique index, then drop the old unique and old column.
        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'id_semester')) {
                $table->string('id_semester', 5)->nullable()->after('semester');
            }
        });

        // Copy data from semester into id_semester
        DB::statement('UPDATE lpr_akademik_mahasiswa SET id_semester = semester');

        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'nama_status_mahasiswa')) {
                $table->renameColumn('status_mahasiswa', 'nama_status_mahasiswa');
            }
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'nama_program_studi')) {
                $table->renameColumn('nama_prodi', 'nama_program_studi');
            }

            // Add other new fields from API
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'nama_semester')) {
                $table->string('nama_semester', 50)->nullable()->after('id_semester');
            }
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'id_status_mahasiswa')) {
                $table->string('id_status_mahasiswa', 10)->nullable()->after('nama_program_studi');
            }
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'biaya_kuliah_smt')) {
                $table->decimal('biaya_kuliah_smt', 16, 2)->nullable()->after('sks_total');
            }
            if (! Schema::hasColumn('lpr_akademik_mahasiswa', 'id_pembiayaan')) {
                $table->string('id_pembiayaan', 10)->nullable()->after('biaya_kuliah_smt');
            }

            // Update column types
            $table->string('id_semester', 5)->change();
            $table->string('nama_status_mahasiswa', 50)->nullable()->change();
            $table->string('nama_program_studi', 200)->change();
        });

        // Create new unique constraint on (institusi_id, registrasi_feeder_id, id_semester)
        $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lpr_akademik_mahasiswa'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);

        if (! in_array('lpr_akademik_mhs_unique', $indexNames)) {
            Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
                $table->unique(['institusi_id', 'registrasi_feeder_id', 'id_semester'], 'lpr_akademik_mhs_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            $table->dropUnique('lpr_akademik_mhs_unique');
        });

        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['nama_semester', 'id_status_mahasiswa', 'biaya_kuliah_smt', 'id_pembiayaan']);
        });

        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            // Rename back to old names
            $table->renameColumn('id_semester', 'semester');
            $table->renameColumn('nama_status_mahasiswa', 'status_mahasiswa');
            $table->renameColumn('nama_program_studi', 'nama_prodi');

            // Restore old column types
            $table->string('semester', 5)->change();
            $table->string('status_mahasiswa', 20)->change();
            $table->string('nama_prodi', 100)->change();
        });

        // Recreate old unique constraint
        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            $table->unique(['institusi_id', 'registrasi_feeder_id', 'semester'], 'lpr_akademik_mhs_unique');
        });
    }
};
