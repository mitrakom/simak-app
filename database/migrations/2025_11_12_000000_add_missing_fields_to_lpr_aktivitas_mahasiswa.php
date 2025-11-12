<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 7 missing fields from GetListAktivitasMahasiswa API to achieve 100% coverage:
     * 1. nama_semester - Nama periode semester
     * 2. keterangan - Deskripsi aktivitas
     * 3. sk_tugas - Nomor SK penugasan
     * 4. tanggal_sk_tugas - Tanggal SK penugasan
     * 5. untuk_kampus_merdeka - Flag MBKM
     * 6. tanggal_mulai - Tanggal mulai aktivitas
     * 7. tanggal_selesai - Tanggal selesai aktivitas
     */
    public function up(): void
    {
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            // Add missing fields from GetListAktivitasMahasiswa API
            $table->string('nama_semester', 50)->nullable()->after('id_semester')->comment('Nama periode semester');
            $table->text('keterangan')->nullable()->after('lokasi')->comment('Deskripsi aktivitas');
            $table->string('sk_tugas', 200)->nullable()->after('keterangan')->comment('Nomor SK penugasan');
            $table->date('tanggal_sk_tugas')->nullable()->after('sk_tugas')->comment('Tanggal SK penugasan');
            $table->boolean('untuk_kampus_merdeka')->default(false)->after('tanggal_sk_tugas')->comment('Flag Kampus Merdeka (MBKM)');
            $table->date('tanggal_mulai')->nullable()->after('untuk_kampus_merdeka')->comment('Tanggal mulai aktivitas');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai')->comment('Tanggal selesai aktivitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lpr_aktivitas_mahasiswa', function (Blueprint $table) {
            $table->dropColumn([
                'nama_semester',
                'keterangan',
                'sk_tugas',
                'tanggal_sk_tugas',
                'untuk_kampus_merdeka',
                'tanggal_mulai',
                'tanggal_selesai',
            ]);
        });
    }
};
