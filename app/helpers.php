<?php

declare(strict_types=1);

if (! function_exists('is_forbidden_institusi_slug')) {
    /**
     * Check if a slug is forbidden for institusi.
     */
    function is_forbidden_institusi_slug(string $slug): bool
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

        return in_array(strtolower($slug), $forbiddenSlugs);
    }
}

if (! function_exists('get_forbidden_institusi_slugs')) {
    /**
     * Get list of forbidden slugs for institusi.
     */
    function get_forbidden_institusi_slugs(): array
    {
        return [
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
    }
}

if (! function_exists('current_institusi_theme')) {
    /**
     * Get current institusi theme configuration.
     */
    function current_institusi_theme(): ?array
    {
        $institusi = request()->route('institusi');

        if (! $institusi instanceof \App\Models\Institusi) {
            return null;
        }

        return $institusi->getThemeConfig();
    }
}

if (! function_exists('theme_color')) {
    /**
     * Get theme color class for current institusi.
     *
     * @param  string  $type  Type of color: 'primary', 'secondary', 'accent'
     * @param  string  $variant  Variant: 'bg', 'text', 'border', 'ring'
     * @param  string  $shade  Shade: '50', '100', '200', '300', '400', '500', '600', '700', '800', '900'
     */
    function theme_color(string $type = 'primary', string $variant = 'bg', string $shade = '500'): string
    {
        $institusi = request()->route('institusi');

        if (! $institusi instanceof \App\Models\Institusi) {
            $color = 'blue'; // Default color
        } else {
            $color = match ($type) {
                'secondary' => $institusi->theme_secondary_color ?? 'purple',
                'accent' => $institusi->theme_accent_color ?? 'indigo',
                default => $institusi->theme_primary_color ?? 'blue',
            };
        }

        return "{$variant}-{$color}-{$shade}";
    }
}

if (! function_exists('theme_gradient')) {
    /**
     * Get gradient background class for current institusi theme.
     */
    function theme_gradient(string $direction = 'br'): string
    {
        $institusi = request()->route('institusi');

        if (! $institusi instanceof \App\Models\Institusi) {
            return 'bg-gradient-to-br from-blue-500 to-purple-600';
        }

        $primary = $institusi->theme_primary_color ?? 'blue';
        $secondary = $institusi->theme_secondary_color ?? 'purple';

        return "bg-gradient-to-{$direction} from-{$primary}-500 to-{$secondary}-600";
    }
}
