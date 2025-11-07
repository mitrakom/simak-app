<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Institusi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'slug',
        'feeder_url',
        'feeder_username',
        'feeder_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'feeder_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($institusi) {
            if (empty($institusi->slug)) {
                $institusi->slug = Str::slug($institusi->nama);
            }
        });
    }

    /**
     * Get the users for the institusi.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the prodis for the institusi.
     */
    public function prodis(): HasMany
    {
        return $this->hasMany(Prodi::class);
    }

    /**
     * Scope a query to only include institusis with feeder configuration.
     */
    public function scopeHasFeederConfig($query)
    {
        return $query->whereNotNull('feeder_url')
            ->whereNotNull('feeder_username')
            ->whereNotNull('feeder_password');
    }

    /**
     * Scope a query to search institusis by name or slug.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    /**
     * Check if institusi has feeder configuration.
     */
    public function hasFeederConfig(): bool
    {
        return !empty($this->feeder_url)
            && !empty($this->feeder_username)
            && !empty($this->feeder_password);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
