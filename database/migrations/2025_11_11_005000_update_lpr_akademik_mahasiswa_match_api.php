<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            // Drop unique constraint before renaming
            $table->dropUnique('lpr_akademik_mhs_unique');
        });

        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            // Rename columns to match API
            $table->renameColumn('semester', 'id_semester');
            $table->renameColumn('status_mahasiswa', 'nama_status_mahasiswa');
            $table->renameColumn('nama_prodi', 'nama_program_studi');
        });

        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            // Add new fields from API
            $table->string('nama_semester', 50)->nullable()->after('id_semester');
            $table->string('id_status_mahasiswa', 10)->nullable()->after('nama_program_studi');
            $table->decimal('biaya_kuliah_smt', 16, 2)->nullable()->after('sks_total');
            $table->string('id_pembiayaan', 10)->nullable()->after('biaya_kuliah_smt');

            // Update column types
            $table->string('id_semester', 5)->change();
            $table->string('nama_status_mahasiswa', 50)->nullable()->change();
            $table->string('nama_program_studi', 200)->change();
        });

        // Recreate unique constraint with new column name
        Schema::table('lpr_akademik_mahasiswa', function (Blueprint $table) {
            $table->unique(['institusi_id', 'registrasi_feeder_id', 'id_semester'], 'lpr_akademik_mhs_unique');
        });
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
