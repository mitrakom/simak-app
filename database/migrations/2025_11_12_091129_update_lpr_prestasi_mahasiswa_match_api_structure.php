<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update lpr_prestasi_mahasiswa table structure to match GetListPrestasiMahasiswa API response.
     *
     * API Fields from GetListPrestasiMahasiswa:
     * - id_prestasi (uuid) - Primary ID from Feeder
     * - id_mahasiswa (uuid) - Foreign key ke mahasiswa
     * - jenis_prestasi (int/string) - Tipe prestasi (akademik, non-akademik, etc)
     * - tingkat_prestasi (string) - Lokal/Nasional/Internasional/Wilayah
     * - nama_prestasi (string) - Nama/judul prestasi
     * - tahun_prestasi (year) - Tahun pencapaian
     * - peringkat (int) - Peringkat yang diraih
     * - penyelenggara (string) - Institusi/organisasi penyelenggara
     *
     * Additional local fields:
     * - institusi_id - For multitenancy
     * - mahasiswa_id - Local FK ke tabel mahasiswa
     * - nim, nama_mahasiswa - Denormalized untuk performa
     */
    public function up(): void
    {
        Schema::table('lpr_prestasi_mahasiswa', function (Blueprint $table) {
            // Rename existing columns to match API
            // Old: prestasi_feeder_id -> New: id_prestasi
            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'prestasi_feeder_id') &&
                ! Schema::hasColumn('lpr_prestasi_mahasiswa', 'id_prestasi')) {
                $table->renameColumn('prestasi_feeder_id', 'id_prestasi');
            }

            // Old: mahasiswa_feeder_id -> New: id_mahasiswa
            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'mahasiswa_feeder_id') &&
                ! Schema::hasColumn('lpr_prestasi_mahasiswa', 'id_mahasiswa')) {
                $table->renameColumn('mahasiswa_feeder_id', 'id_mahasiswa');
            }

            // Add new columns from API if not exists
            if (! Schema::hasColumn('lpr_prestasi_mahasiswa', 'jenis_prestasi')) {
                $table->string('jenis_prestasi', 50)->nullable()->after('id_mahasiswa')
                    ->comment('Jenis/kategori prestasi');
            }

            if (! Schema::hasColumn('lpr_prestasi_mahasiswa', 'peringkat')) {
                $table->integer('peringkat')->nullable()->after('nama_prestasi')
                    ->comment('Peringkat/ranking yang diraih');
            }

            // Add registrasi_feeder_id if not exists (optional FK to registrasi mahasiswa)
            if (! Schema::hasColumn('lpr_prestasi_mahasiswa', 'registrasi_feeder_id')) {
                $table->uuid('registrasi_feeder_id')->nullable()->after('jenis_prestasi')
                    ->index()
                    ->comment('ID Registrasi Mahasiswa dari Feeder (id_registrasi_mahasiswa) - Optional');
            }
        });

        // Note: Unique constraint 'lpr_prestasi_mhs_unique' already uses correct columns (institusi_id, id_prestasi)
        // No need to drop and recreate it
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_prestasi_mahasiswa', function (Blueprint $table) {
            // Reverse column renames
            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'id_prestasi')) {
                $table->renameColumn('id_prestasi', 'prestasi_feeder_id');
            }

            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'id_mahasiswa')) {
                $table->renameColumn('id_mahasiswa', 'mahasiswa_feeder_id');
            }

            // Remove new columns
            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'jenis_prestasi')) {
                $table->dropColumn('jenis_prestasi');
            }

            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'peringkat')) {
                $table->dropColumn('peringkat');
            }

            if (Schema::hasColumn('lpr_prestasi_mahasiswa', 'registrasi_feeder_id')) {
                $table->dropColumn('registrasi_feeder_id');
            }
        });
    }
};
