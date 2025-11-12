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
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Field dari GetListMahasiswa (Essential untuk monitoring akademik)
            $table->char('jenis_kelamin', 1)->nullable()->after('nama');
            $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
            $table->integer('id_agama')->nullable()->after('tanggal_lahir');
            $table->string('nama_agama', 50)->nullable()->after('id_agama');
            $table->string('id_status_mahasiswa', 10)->nullable()->after('angkatan');
            $table->string('nama_status_mahasiswa', 50)->nullable()->after('id_status_mahasiswa');
            $table->string('id_periode_masuk', 10)->nullable()->after('nama_status_mahasiswa'); // id_periode
            $table->string('nama_periode_masuk', 50)->nullable()->after('id_periode_masuk');
            $table->decimal('ipk', 3, 2)->nullable()->after('nama_periode_masuk');
            $table->integer('total_sks')->nullable()->after('ipk');

            // Field biodata detail (optional - dari GetBiodataMahasiswa)
            $table->string('tempat_lahir', 100)->nullable()->after('tanggal_lahir');
            $table->string('nik', 20)->nullable()->after('tempat_lahir');
            $table->string('nisn', 20)->nullable()->after('nik');
            $table->string('email', 100)->nullable()->after('nisn');
            $table->string('handphone', 20)->nullable()->after('email');
            $table->string('nama_ibu_kandung', 100)->nullable()->after('handphone');

            // Flag untuk tracking biodata detail sync
            $table->boolean('has_biodata_detail')->default(false)->after('nama_ibu_kandung');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_kelamin',
                'tanggal_lahir',
                'id_agama',
                'nama_agama',
                'id_status_mahasiswa',
                'nama_status_mahasiswa',
                'id_periode_masuk',
                'nama_periode_masuk',
                'ipk',
                'total_sks',
                'tempat_lahir',
                'nik',
                'nisn',
                'email',
                'handphone',
                'nama_ibu_kandung',
                'has_biodata_detail',
            ]);
        });
    }
};
