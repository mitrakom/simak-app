<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprDosenAkreditasi extends Model
{
    protected $table = 'lpr_dosen_akreditasi';

    protected $fillable = [
        'institusi_id',
        'dosen_id',
        'dosen_feeder_id',
        'nidn',
        'nama_dosen',
        // Pendidikan
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
        // Jabatan Fungsional
        'jabatan_fungsional_saat_ini',
        'sk_jabatan_fungsional',
        'tanggal_sk_jabatan_fungsional',
        // Sertifikasi
        'sudah_sertifikasi_dosen',
        'tahun_sertifikasi_dosen',
        'sk_sertifikasi_dosen',
        'bidang_sertifikasi_dosen',
        // Summary untuk akreditasi
        'jenjang_pendidikan_tertinggi',
        'kesesuaian_bidang_ilmu',
        'status_dosen',
    ];

    protected $casts = [
        'pendidikan_s1_tahun_lulus' => 'integer',
        'pendidikan_s2_tahun_lulus' => 'integer',
        'pendidikan_s3_tahun_lulus' => 'integer',
        'pendidikan_profesi_tahun_lulus' => 'integer',
        'tanggal_sk_jabatan_fungsional' => 'date',
        'sudah_sertifikasi_dosen' => 'boolean',
        'tahun_sertifikasi_dosen' => 'integer',
        'kesesuaian_bidang_ilmu' => 'boolean',
    ];

    /**
     * Relationship dengan Institusi
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Relationship dengan Dosen
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    /**
     * Get highest education level for this dosen
     */
    public function getHighestEducationAttribute(): string
    {
        if ($this->pendidikan_s3_tahun_lulus) {
            return 'S3';
        } elseif ($this->pendidikan_s2_tahun_lulus) {
            return 'S2';
        } elseif ($this->pendidikan_profesi_tahun_lulus) {
            return 'Profesi';
        } elseif ($this->pendidikan_s1_tahun_lulus) {
            return 'S1';
        }
        return 'Tidak Diketahui';
    }

    /**
     * Check if dosen has functional position
     */
    public function hasFunctionalPositionAttribute(): bool
    {
        return !empty($this->jabatan_fungsional_saat_ini);
    }

    /**
     * Check if dosen is certified
     */
    public function isCertifiedAttribute(): bool
    {
        return $this->sudah_sertifikasi_dosen && !empty($this->tahun_sertifikasi_dosen);
    }
}
