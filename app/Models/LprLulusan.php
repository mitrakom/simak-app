<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprLulusan extends Model
{
    protected $table = 'lpr_lulusan';

    protected $fillable = [
        'institusi_id',
        'mahasiswa_id',
        'prodi_id',
        'mahasiswa_feeder_id',
        'registrasi_feeder_id',
        'nim',
        'nama_mahasiswa',
        'nama_prodi',
        'angkatan',
        'status_keluar',
        'tanggal_keluar',
        'ipk_lulusan',
        'masa_studi_bulan',
        'nomor_ijazah',
        'nomor_sk_yudisium',
        'tanggal_sk_yudisium',
        'judul_skripsi',
        'periode_keluar',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date',
        'tanggal_sk_yudisium' => 'date',
        'ipk_lulusan' => 'decimal:2',
        'angkatan' => 'integer',
        'masa_studi_bulan' => 'integer',
    ];

    /**
     * Relationship to Institusi
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Relationship to Mahasiswa
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /**
     * Relationship to Prodi
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
