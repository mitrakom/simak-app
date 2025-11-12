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
        'theme_primary_color',
        'theme_secondary_color',
        'theme_accent_color',
        'logo_path',
        'favicon_path',
        'custom_css',
        'theme_mode',
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

            // Validasi slug terlarang
            static::validateSlug($institusi->slug);
        });

        static::updating(function ($institusi) {
            // Validasi slug terlarang saat update
            if ($institusi->isDirty('slug')) {
                static::validateSlug($institusi->slug);
            }
        });
    }

    /**
     * Validasi slug institusi.
     *
     * @throws \InvalidArgumentException
     */
    protected static function validateSlug(string $slug): void
    {
        $forbiddenSlugs = [
            'default',
            'admin',
            'api',
            'auth',
            'login',
            'register',
            'logout',
            'test',
            'testing',
            'staging',
            'production',
            'dev',
            'development',
            'user',
            'users',
            'dashboard',
            'home',
            'about',
            'contact',
            'terms',
            'privacy',
            'help',
            'support',
            'docs',
            'documentation',
        ];

        if (in_array(strtolower($slug), $forbiddenSlugs)) {
            throw new \InvalidArgumentException("Slug '{$slug}' tidak diperbolehkan. Gunakan slug yang lebih spesifik untuk institusi Anda.");
        }

        // Validasi format slug (hanya lowercase, angka, dan dash)
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new \InvalidArgumentException('Slug harus berformat lowercase, angka, dan dash tanpa spasi atau karakter khusus.');
        }
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
        return ! empty($this->feeder_url)
            && ! empty($this->feeder_username)
            && ! empty($this->feeder_password);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get theme configuration as array.
     */
    public function getThemeConfig(): array
    {
        return [
            'primary' => $this->theme_primary_color ?? 'blue',
            'secondary' => $this->theme_secondary_color ?? 'purple',
            'accent' => $this->theme_accent_color ?? 'indigo',
            'mode' => $this->theme_mode ?? 'auto',
            'logo' => $this->logo_path,
            'favicon' => $this->favicon_path,
            'custom_css' => $this->custom_css,
        ];
    }

    /**
     * Get available theme colors.
     */
    public static function getAvailableColors(): array
    {
        return [
            'slate' => ['name' => 'Slate', 'class' => 'bg-slate-500'],
            'gray' => ['name' => 'Gray', 'class' => 'bg-gray-500'],
            'zinc' => ['name' => 'Zinc', 'class' => 'bg-zinc-500'],
            'neutral' => ['name' => 'Neutral', 'class' => 'bg-neutral-500'],
            'stone' => ['name' => 'Stone', 'class' => 'bg-stone-500'],
            'red' => ['name' => 'Red', 'class' => 'bg-red-500'],
            'orange' => ['name' => 'Orange', 'class' => 'bg-orange-500'],
            'amber' => ['name' => 'Amber', 'class' => 'bg-amber-500'],
            'yellow' => ['name' => 'Yellow', 'class' => 'bg-yellow-500'],
            'lime' => ['name' => 'Lime', 'class' => 'bg-lime-500'],
            'green' => ['name' => 'Green', 'class' => 'bg-green-500'],
            'emerald' => ['name' => 'Emerald', 'class' => 'bg-emerald-500'],
            'teal' => ['name' => 'Teal', 'class' => 'bg-teal-500'],
            'cyan' => ['name' => 'Cyan', 'class' => 'bg-cyan-500'],
            'sky' => ['name' => 'Sky', 'class' => 'bg-sky-500'],
            'blue' => ['name' => 'Blue', 'class' => 'bg-blue-500'],
            'indigo' => ['name' => 'Indigo', 'class' => 'bg-indigo-500'],
            'violet' => ['name' => 'Violet', 'class' => 'bg-violet-500'],
            'purple' => ['name' => 'Purple', 'class' => 'bg-purple-500'],
            'fuchsia' => ['name' => 'Fuchsia', 'class' => 'bg-fuchsia-500'],
            'pink' => ['name' => 'Pink', 'class' => 'bg-pink-500'],
            'rose' => ['name' => 'Rose', 'class' => 'bg-rose-500'],
        ];
    }

    /**
     * Get Tailwind color classes for current theme.
     */
    public function getThemeClasses(): array
    {
        $primary = $this->theme_primary_color ?? 'blue';
        $secondary = $this->theme_secondary_color ?? 'purple';

        return [
            // Background colors
            'bg-primary' => "bg-{$primary}-500",
            'bg-primary-dark' => "bg-{$primary}-600",
            'bg-primary-light' => "bg-{$primary}-50",
            'bg-secondary' => "bg-{$secondary}-500",

            // Text colors
            'text-primary' => "text-{$primary}-600",
            'text-primary-dark' => "text-{$primary}-700",
            'text-secondary' => "text-{$secondary}-600",

            // Border colors
            'border-primary' => "border-{$primary}-500",
            'border-secondary' => "border-{$secondary}-500",

            // Hover states
            'hover-bg-primary' => "hover:bg-{$primary}-600",
            'hover-text-primary' => "hover:text-{$primary}-700",

            // Focus states
            'focus-ring-primary' => "focus:ring-{$primary}-500",
        ];
    }
}
