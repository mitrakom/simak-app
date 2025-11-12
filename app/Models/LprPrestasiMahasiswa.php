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
        'id_mahasiswa',           // UUID from Feeder API
        'id_prestasi',            // UUID from Feeder API
        'jenis_prestasi',         // Type of achievement
        'registrasi_feeder_id',   // Optional registration ID
        'nim',
        'nama_mahasiswa',
        'nama_prestasi',
        'peringkat',              // Ranking/position achieved
        'tingkat_prestasi',       // Level: Lokal/Nasional/Internasional/Wilayah
        'tahun_prestasi',
        'penyelenggara',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tahun_prestasi' => 'integer',
        'peringkat' => 'integer',
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
