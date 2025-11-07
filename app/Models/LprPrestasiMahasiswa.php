<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprPrestasiMahasiswa extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'lpr_prestasi_mahasiswa';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'institusi_id',
        'mahasiswa_id',
        'mahasiswa_feeder_id',
        'prestasi_feeder_id',
        'nim',
        'nama_mahasiswa',
        'nama_prestasi',
        'tingkat_prestasi',
        'tahun_prestasi',
        'penyelenggara',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tahun_prestasi' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
