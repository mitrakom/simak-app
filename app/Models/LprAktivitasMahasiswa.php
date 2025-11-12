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
        // Feeder IDs - exact API field names (100% match)
        'id_anggota',              // UUID anggota (UNIQUE KEY)
        'id_aktivitas',            // UUID aktivitas
        'id_mahasiswa',            // UUID individu mahasiswa
        'id_registrasi_mahasiswa', // UUID registrasi mahasiswa
        // Denormalized fields from API
        'nim',
        'nama_mahasiswa',
        'judul',                   // Activity title
        'id_jenis_aktivitas',      // Activity type ID
        'nama_jenis_aktivitas',    // Activity type name
        'jenis_peran',             // Member role code
        'nama_jenis_peran',        // Member role name
        'lokasi',                  // Activity location
        'id_semester',             // Period code
        'nama_semester',           // Period name
        'keterangan',              // Activity description
        'sk_tugas',                // Assignment decree number
        'tanggal_sk_tugas',        // Assignment decree date
        'untuk_kampus_merdeka',    // MBKM flag
        'tanggal_mulai',           // Start date
        'tanggal_selesai',         // End date
    ];

    protected $casts = [
        'untuk_kampus_merdeka' => 'boolean',
        'tanggal_sk_tugas' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Get the institusi that owns the aktivitas mahasiswa.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }
}
