@props(['institusi' => null])

@php
    $institusi = $institusi ?? request()->route('institusi');
    $themeConfig = $institusi?->getThemeConfig() ?? [
        'primary' => 'blue',
        'secondary' => 'purple',
        'accent' => 'indigo',
        'mode' => 'auto',
    ];
    
    // Map Tailwind colors to CSS values
    $colorMap = [
        'slate' => ['50' => '#f8fafc', '500' => '#64748b', '600' => '#475569', '700' => '#334155'],
        'gray' => ['50' => '#f9fafb', '500' => '#6b7280', '600' => '#4b5563', '700' => '#374151'],
        'red' => ['50' => '#fef2f2', '500' => '#ef4444', '600' => '#dc2626', '700' => '#b91c1c'],
        'orange' => ['50' => '#fff7ed', '500' => '#f97316', '600' => '#ea580c', '700' => '#c2410c'],
        'amber' => ['50' => '#fffbeb', '500' => '#f59e0b', '600' => '#d97706', '700' => '#b45309'],
        'yellow' => ['50' => '#fefce8', '500' => '#eab308', '600' => '#ca8a04', '700' => '#a16207'],
        'lime' => ['50' => '#f7fee7', '500' => '#84cc16', '600' => '#65a30d', '700' => '#4d7c0f'],
        'green' => ['50' => '#f0fdf4', '500' => '#22c55e', '600' => '#16a34a', '700' => '#15803d'],
        'emerald' => ['50' => '#ecfdf5', '500' => '#10b981', '600' => '#059669', '700' => '#047857'],
        'teal' => ['50' => '#f0fdfa', '500' => '#14b8a6', '600' => '#0d9488', '700' => '#0f766e'],
        'cyan' => ['50' => '#ecfeff', '500' => '#06b6d4', '600' => '#0891b2', '700' => '#0e7490'],
        'sky' => ['50' => '#f0f9ff', '500' => '#0ea5e9', '600' => '#0284c7', '700' => '#0369a1'],
        'blue' => ['50' => '#eff6ff', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8'],
        'indigo' => ['50' => '#eef2ff', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca'],
        'violet' => ['50' => '#f5f3ff', '500' => '#8b5cf6', '600' => '#7c3aed', '700' => '#6d28d9'],
        'purple' => ['50' => '#faf5ff', '500' => '#a855f7', '600' => '#9333ea', '700' => '#7e22ce'],
        'fuchsia' => ['50' => '#fdf4ff', '500' => '#d946ef', '600' => '#c026d3', '700' => '#a21caf'],
        'pink' => ['50' => '#fdf2f8', '500' => '#ec4899', '600' => '#db2777', '700' => '#be185d'],
        'rose' => ['50' => '#fff1f2', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c'],
    ];
    
    $primary = $colorMap[$themeConfig['primary']] ?? $colorMap['blue'];
    $secondary = $colorMap[$themeConfig['secondary']] ?? $colorMap['purple'];
    $accent = $colorMap[$themeConfig['accent']] ?? $colorMap['indigo'];
@endphp

<style>
    :root {
        /* Primary colors */
        --color-primary-50: {{ $primary['50'] }};
        --color-primary-500: {{ $primary['500'] }};
        --color-primary-600: {{ $primary['600'] }};
        --color-primary-700: {{ $primary['700'] }};
        
        /* Secondary colors */
        --color-secondary-50: {{ $secondary['50'] }};
        --color-secondary-500: {{ $secondary['500'] }};
        --color-secondary-600: {{ $secondary['600'] }};
        --color-secondary-700: {{ $secondary['700'] }};
        
        /* Accent colors */
        --color-accent-50: {{ $accent['50'] }};
        --color-accent-500: {{ $accent['500'] }};
        --color-accent-600: {{ $accent['600'] }};
        --color-accent-700: {{ $accent['700'] }};
    }
    
    /* Apply theme colors to specific elements */
    .theme-primary-bg {
        background-color: var(--color-primary-500) !important;
    }
    
    .theme-primary-bg-light {
        background-color: var(--color-primary-50) !important;
    }
    
    .theme-primary-bg-dark {
        background-color: var(--color-primary-600) !important;
    }
    
    .theme-primary-text {
        color: var(--color-primary-600) !important;
    }
    
    .theme-primary-border {
        border-color: var(--color-primary-500) !important;
    }
    
    .theme-gradient {
        background: linear-gradient(to bottom right, var(--color-primary-500), var(--color-secondary-600)) !important;
    }
    
    /* Hover states */
    .theme-hover-bg-primary:hover {
        background-color: var(--color-primary-600) !important;
    }
    
    .theme-hover-text-primary:hover {
        color: var(--color-primary-700) !important;
    }
    
    /* Focus states */
    .theme-focus-ring:focus {
        --tw-ring-color: var(--color-primary-500) !important;
    }
    
    @if($themeConfig['custom_css'])
        /* Custom CSS from Institusi */
        {!! $themeConfig['custom_css'] !!}
    @endif
</style>
