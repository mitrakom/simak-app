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
        Schema::table('lpr_dosen_akreditasi', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['pendidikan_tertinggi', 'jabatan_fungsional', 'sudah_sertifikasi']);

            // Add columns untuk pendidikan tertinggi (dari GetRiwayatPendidikanDosen)
            $table->string('pendidikan_s1_bidang_studi', 100)->nullable()->after('nama_dosen');
            $table->string('pendidikan_s1_perguruan_tinggi', 100)->nullable()->after('pendidikan_s1_bidang_studi');
            $table->year('pendidikan_s1_tahun_lulus')->nullable()->after('pendidikan_s1_perguruan_tinggi');

            $table->string('pendidikan_s2_bidang_studi', 100)->nullable()->after('pendidikan_s1_tahun_lulus');
            $table->string('pendidikan_s2_perguruan_tinggi', 100)->nullable()->after('pendidikan_s2_bidang_studi');
            $table->year('pendidikan_s2_tahun_lulus')->nullable()->after('pendidikan_s2_perguruan_tinggi');

            $table->string('pendidikan_s3_bidang_studi', 100)->nullable()->after('pendidikan_s2_tahun_lulus');
            $table->string('pendidikan_s3_perguruan_tinggi', 100)->nullable()->after('pendidikan_s3_bidang_studi');
            $table->year('pendidikan_s3_tahun_lulus')->nullable()->after('pendidikan_s3_perguruan_tinggi');

            $table->string('pendidikan_profesi_bidang_studi', 100)->nullable()->after('pendidikan_s3_tahun_lulus');
            $table->string('pendidikan_profesi_perguruan_tinggi', 100)->nullable()->after('pendidikan_profesi_bidang_studi');
            $table->year('pendidikan_profesi_tahun_lulus')->nullable()->after('pendidikan_profesi_perguruan_tinggi');

            // Add columns untuk jabatan fungsional (dari GetRiwayatFungsionalDosen)
            $table->string('jabatan_fungsional_saat_ini', 50)->nullable()->after('pendidikan_profesi_tahun_lulus');
            $table->string('sk_jabatan_fungsional', 100)->nullable()->after('jabatan_fungsional_saat_ini');
            $table->date('tanggal_sk_jabatan_fungsional')->nullable()->after('sk_jabatan_fungsional');

            // Add columns untuk sertifikasi (dari GetRiwayatSertifikasiDosen)
            $table->boolean('sudah_sertifikasi_dosen')->default(false)->after('tanggal_sk_jabatan_fungsional');
            $table->year('tahun_sertifikasi_dosen')->nullable()->after('sudah_sertifikasi_dosen');
            $table->string('sk_sertifikasi_dosen', 50)->nullable()->after('tahun_sertifikasi_dosen');
            $table->string('bidang_sertifikasi_dosen', 100)->nullable()->after('sk_sertifikasi_dosen');

            // Add summary columns untuk akreditasi
            $table->string('jenjang_pendidikan_tertinggi', 20)->nullable()->after('bidang_sertifikasi_dosen');
            $table->boolean('kesesuaian_bidang_ilmu')->default(true)->after('jenjang_pendidikan_tertinggi');
            $table->string('status_dosen', 30)->default('Aktif')->after('kesesuaian_bidang_ilmu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_dosen_akreditasi', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'pendidikan_s1_bidang_studi',
                'pendidikan_s1_perguruan_tinggi',
                'pendidikan_s1_tahun_lulus',
                'pendidikan_s2_bidang_studi',
                'pendidikan_s2_perguruan_tinggi',
                'pendidikan_s2_tahun_lulus',
                'pendidikan_s3_bidang_studi',
                'pendidikan_s3_perguruan_tinggi',
                'pendidikan_s3_tahun_lulus',
                'pendidikan_profesi_bidang_studi',
                'pendidikan_profesi_perguruan_tinggi',
                'pendidikan_profesi_tahun_lulus',
                'jabatan_fungsional_saat_ini',
                'sk_jabatan_fungsional',
                'tanggal_sk_jabatan_fungsional',
                'sudah_sertifikasi_dosen',
                'tahun_sertifikasi_dosen',
                'sk_sertifikasi_dosen',
                'bidang_sertifikasi_dosen',
                'jenjang_pendidikan_tertinggi',
                'kesesuaian_bidang_ilmu',
                'status_dosen',
            ]);

            // Add back old columns
            $table->string('pendidikan_tertinggi', 20)->nullable()->after('nama_dosen');
            $table->string('jabatan_fungsional', 50)->nullable()->after('pendidikan_tertinggi');
            $table->boolean('sudah_sertifikasi')->default(false)->after('jabatan_fungsional');
        });
    }
};
