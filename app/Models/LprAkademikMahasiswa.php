<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprAkademikMahasiswa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'lpr_akademik_mahasiswa';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'institusi_id',
        'mahasiswa_id',
        'mahasiswa_feeder_id',
        'registrasi_feeder_id',
        'nim',
        'nama_mahasiswa',
        'angkatan',
        'nama_prodi',
        'semester',
        'ips',
        'ipk',
        'sks_semester',
        'sks_total',
        'status_mahasiswa',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'angkatan' => 'integer',
        'ips' => 'decimal:2',
        'ipk' => 'decimal:2',
        'sks_semester' => 'integer',
        'sks_total' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the institusi that owns the academic record
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Get the mahasiswa that owns the academic record
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
