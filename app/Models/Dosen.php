<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dosen extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'dosen';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'institusi_id',
        'feeder_id',
        'nidn',
        'nuptk',
        'nip',
        'nama',
        'jenis_kelamin',
        'id_agama',
        'nama_agama',
        'tanggal_lahir',
        'id_status_aktif',
        'nama_status_aktif',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'institusi_id' => 'integer',
        'id_agama' => 'integer',
    ];

    /**
     * Get the institusi that owns the dosen.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }
}
