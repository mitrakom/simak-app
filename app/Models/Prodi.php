<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\InstitusiScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prodi extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     *
     * Register global scope untuk multi-tenancy
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitusiScope);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institusi_id',
        'id_prodi', // ID Prodi dari Feeder (id_prodi) - UUID
        'kode_program_studi', // Kode program studi
        'nama_program_studi', // Nama program studi
        'status', // Status prodi (A = Aktif, dll)
        'id_jenjang_pendidikan', // ID Jenjang (30 = S1, dll)
        'nama_jenjang_pendidikan', // Nama jenjang (S1, S2, S3, dll)
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'institusi_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the institusi that owns the prodi.
     */
    public function institusi(): BelongsTo
    {
        return $this->belongsTo(Institusi::class);
    }

    /**
     * Scope a query to only include prodis from a specific institusi.
     */
    public function scopeByInstitusi($query, int $institusiId)
    {
        return $query->where('institusi_id', $institusiId);
    }

    /**
     * Scope a query to search prodis by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('nama', 'like', "%{$search}%");
    }

    /**
     * Scope a query to include institusi relationship.
     */
    public function scopeWithInstitusi($query)
    {
        return $query->with('institusi');
    }
}
