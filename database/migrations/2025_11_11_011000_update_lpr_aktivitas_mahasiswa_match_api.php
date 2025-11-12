<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update lpr_aktivitas_mahasiswa table to match API structure:
     * 
     * API: GetListAnggotaAktivitasMahasiswa (PRIMARY endpoint for sync)
     * Returns: List of activity members with 8 fields
     * 
     * Key Changes:
     * 1. Rename: anggota_aktivitas_feeder_id → id_anggota (UUID, UNIQUE per member)
     * 2. Rename: judul_aktivitas → judul (activity title)
     * 3. Rename: jenis_aktivitas → nama_jenis_aktivitas (activity type name)
     * 4. Rename: peran_mahasiswa → nama_jenis_peran (member role name)
     * 5. Rename: semester → id_semester (period code)
     * 6. Add: id_jenis_aktivitas (activity type ID)
     * 7. Add: jenis_peran (member role code)
     * 8. Remove: apakah_mbkm (moved to activity level, not member level)
     * 
     * Unique Constraint: (institusi_id, id_anggota)
     * - id_anggota is UUID for activity membership (one person can join multiple times in different roles)
     */
    public function up(): void
    {
        // Safer: add new id_anggota column, copy data from anggota_aktivitas_feeder_id,
        // create new unique index, and keep old index in place to avoid FK issues.
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'id_anggota')) {
                $table->uuid('id_anggota')->nullable()->after('anggota_aktivitas_feeder_id');
            }
        });

        // Copy existing values
        DB::statement('UPDATE lpr_aktivitas_mahasiswa SET id_anggota = anggota_aktivitas_feeder_id');

        // Rename other columns
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'judul')) {
                $table->renameColumn('judul_aktivitas', 'judul');
            }
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'nama_jenis_aktivitas')) {
                $table->renameColumn('jenis_aktivitas', 'nama_jenis_aktivitas');
            }
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'nama_jenis_peran')) {
                $table->renameColumn('peran_mahasiswa', 'nama_jenis_peran');
            }
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'id_semester')) {
                $table->renameColumn('semester', 'id_semester');
            }
        });

        // Add new columns and update types
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'id_jenis_aktivitas')) {
                $table->string('id_jenis_aktivitas', 10)->nullable()->after('nama_jenis_aktivitas')->comment('ID Jenis Aktivitas dari API');
            }
            if (! Schema::hasColumn('lpr_aktivitas_mahasiswa', 'jenis_peran')) {
                $table->string('jenis_peran', 10)->nullable()->after('nama_jenis_peran')->comment('Kode Jenis Peran dari API');
            }

            $table->string('judul', 500)->change();
            $table->string('nama_jenis_aktivitas', 100)->change();
            $table->string('nama_jenis_peran', 100)->nullable()->change();
            $table->string('id_semester', 5)->nullable()->change();

            // Remove field that's not in member API if present
            if (Schema::hasColumn('lpr_aktivitas_mahasiswa', 'apakah_mbkm')) {
                $table->dropColumn('apakah_mbkm');
            }
        });

        // Create unique constraint on new column if not exists
        $indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lpr_aktivitas_mahasiswa'");
        $indexNames = array_map(fn($r) => $r->INDEX_NAME, $indexes);
        if (! in_array('lpr_aktivitas_mhs_unique', $indexNames)) {
            Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
                $table->unique(['institusi_id', 'id_anggota'], 'lpr_aktivitas_mhs_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique('lpr_aktivitas_mhs_unique');

            // Remove added columns
            $table->dropColumn(['id_jenis_aktivitas', 'jenis_peran']);

            // Add back removed column
            $table->boolean('apakah_mbkm')->default(false)->after('lokasi');
        });

        // Rename columns back to old names
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            $table->renameColumn('id_anggota', 'anggota_aktivitas_feeder_id');
            $table->renameColumn('judul', 'judul_aktivitas');
            $table->renameColumn('nama_jenis_aktivitas', 'jenis_aktivitas');
            $table->renameColumn('nama_jenis_peran', 'peran_mahasiswa');
            $table->renameColumn('id_semester', 'semester');
        });

        // Restore old column types
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            $table->string('judul_aktivitas', 500)->change();
            $table->string('jenis_aktivitas', 50)->change();
            $table->string('peran_mahasiswa', 20)->nullable()->change();
            $table->string('semester', 5)->nullable()->change();

            // Recreate old unique constraint
            $table->unique(['institusi_id', 'anggota_aktivitas_feeder_id'], 'lpr_aktivitas_mhs_unique');
        });
    }
};
