@props([
    'variant' => 'primary', // primary, secondary, success, danger, warning, info
    'size' => 'md', // sm, md, lg
    'dot' => false,
])

@php
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        'lg' => 'px-3 py-1.5 text-base',
    ];
    
    $variantClasses = [
        'primary' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'secondary' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'info' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
    ];
    
    $dotColors = [
        'primary' => 'bg-blue-600',
        'secondary' => 'bg-gray-600',
        'success' => 'bg-green-600',
        'danger' => 'bg-red-600',
        'warning' => 'bg-yellow-600',
        'info' => 'bg-cyan-600',
    ];
    
    $classes = 'inline-flex items-center gap-1.5 font-medium rounded-full ' . $sizeClasses[$size] . ' ' . $variantClasses[$variant];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="size-2 rounded-full {{ $dotColors[$variant] }}"></span>
    @endif
    {{ $slot }}
</span>
