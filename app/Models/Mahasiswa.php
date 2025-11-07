<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mahasiswa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'mahasiswa';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'institusi_id',
        'prodi_id',
        'feeder_id',
        'nim',
        'nama',
        'angkatan',
        // From GetListMahasiswa
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
        // From GetBiodataMahasiswa (optional)
        'tempat_lahir',
        'nik',
        'nisn',
        'email',
        'handphone',
        'nama_ibu_kandung',
        'has_biodata_detail',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'institusi_id' => 'integer',
        'prodi_id' => 'integer',
        'id_agama' => 'integer',
        'angkatan' => 'integer',
        'ipk' => 'decimal:2',
        'total_sks' => 'integer',
        'has_biodata_detail' => 'boolean',
    ];

    /**
     * Get the institusi that owns the mahasiswa.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Get the prodi that owns the mahasiswa.
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
