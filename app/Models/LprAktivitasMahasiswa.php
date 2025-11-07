<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprAktivitasMahasiswa extends Model
{
    protected $table = 'lpr_aktivitas_mahasiswa';

    protected $fillable = [
        'institusi_id',
        'mahasiswa_id',
        'anggota_aktivitas_feeder_id',
        'aktivitas_feeder_id',
        'mahasiswa_feeder_id',
        'registrasi_feeder_id',
        'nim',
        'nama_mahasiswa',
        'judul_aktivitas',
        'jenis_aktivitas',
        'lokasi',
        'apakah_mbkm',
        'semester',
        'peran_mahasiswa',
    ];

    protected $casts = [
        'apakah_mbkm' => 'boolean',
    ];

    /**
     * Get the institusi that owns the aktivitas mahasiswa.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Get the mahasiswa that owns the aktivitas.
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
