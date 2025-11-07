<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprBimbinganTa extends Model
{
    protected $table = 'lpr_bimbingan_ta';

    protected $fillable = [
        'institusi_id',
        'mahasiswa_id',
        'dosen_id',
        'id_bimbing_mahasiswa',
        'id_aktivitas',
        'judul',
        'id_kategori_kegiatan',
        'nama_kategori_kegiatan',
        'id_dosen',
        'nidn',
        'nuptk',
        'nama_dosen',
        'pembimbing_ke',
    ];

    protected $casts = [
        'pembimbing_ke' => 'integer',
        'id_kategori_kegiatan' => 'integer',
    ];

    /**
     * Relationship dengan Institusi
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Relationship dengan Mahasiswa
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /**
     * Relationship dengan Dosen
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
