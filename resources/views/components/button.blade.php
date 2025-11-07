@props([
    'variant' => 'primary', // primary, secondary, success, danger, warning, info
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'outline' => false,
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
    
    $variantClasses = [
        'primary' => $outline 
            ? 'border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:ring-blue-500'
            : 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => $outline
            ? 'border-2 border-gray-600 text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-gray-500'
            : 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        'success' => $outline
            ? 'border-2 border-green-600 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 focus:ring-green-500'
            : 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => $outline
            ? 'border-2 border-red-600 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 focus:ring-red-500'
            : 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'warning' => $outline
            ? 'border-2 border-yellow-600 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 focus:ring-yellow-500'
            : 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        'info' => $outline
            ? 'border-2 border-cyan-600 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 focus:ring-cyan-500'
            : 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500',
    ];
    
    $classes = $baseClasses . ' ' . $sizeClasses[$size] . ' ' . $variantClasses[$variant];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
