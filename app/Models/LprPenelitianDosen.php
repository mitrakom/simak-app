<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LprPenelitianDosen extends Model
{
    use HasFactory;

    protected $table = 'lpr_penelitian_dosen';

    protected $fillable = [
        'institusi_id',
        'dosen_id',
        'dosen_feeder_id',
        'penelitian_feeder_id',
        'nidn',
        'nama_dosen',
        'judul_penelitian',
        'tahun_kegiatan',
        'nama_lembaga_iptek',
    ];

    protected $casts = [
        'institusi_id' => 'integer',
        'dosen_id' => 'integer',
    ];

    /**
     * Get the institusi that owns the penelitian dosen record.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Get the dosen that owns the penelitian record.
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
