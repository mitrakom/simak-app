<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update mahasiswa table structure to match GetListMahasiswa API response:
     * - Rename: nama → nama_mahasiswa
     * - Rename: id_periode_masuk → id_periode
     * - Add: nipd (NIPD mahasiswa)
     * - Add: id_sms (ID SMS - periode registrasi)
     */
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Rename columns to match API
            $table->renameColumn('nama', 'nama_mahasiswa');
            $table->renameColumn('id_periode_masuk', 'id_periode');
        });

        Schema::table('mahasiswa', function (Blueprint $table) {
            // Add missing columns from API
            $table->string('nipd', 20)->nullable()->after('nim')->comment('NIPD Mahasiswa');
            $table->uuid('id_sms')->nullable()->after('registrasi_feeder_id')->comment('ID SMS - Periode Registrasi dari Feeder');

            // Update column lengths to match API
            $table->string('nama_mahasiswa', 200)->change(); // Allow longer names
            $table->string('id_status_mahasiswa', 20)->nullable()->change(); // Allow longer status codes
            $table->string('nama_status_mahasiswa', 100)->nullable()->change(); // Allow longer status names
            $table->string('id_periode', 20)->nullable()->change(); // Allow longer period codes
            $table->string('nama_periode_masuk', 100)->nullable()->change(); // Allow longer period names
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['nipd', 'id_sms']);
        });

        Schema::table('mahasiswa', function (Blueprint $table) {
            // Rename back to old names
            $table->renameColumn('nama_mahasiswa', 'nama');
            $table->renameColumn('id_periode', 'id_periode_masuk');

            // Restore old column types
            $table->string('nama', 100)->change();
            $table->string('id_status_mahasiswa', 10)->change();
            $table->string('nama_status_mahasiswa', 50)->change();
            $table->string('id_periode_masuk', 10)->change();
            $table->string('nama_periode_masuk', 50)->change();
        });
    }
};
