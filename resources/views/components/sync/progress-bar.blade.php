@props([
    'progress' => null,
    'color' => 'blue',
])

@php
    // Define color classes for proper Tailwind compilation
    $progressBarClass = match($color) {
        'blue' => 'bg-blue-600',
        'purple' => 'bg-purple-600',
        'green' => 'bg-green-600',
        'indigo' => 'bg-indigo-600',
        'cyan' => 'bg-cyan-600',
        'yellow' => 'bg-yellow-600',
        'emerald' => 'bg-emerald-600',
        'pink' => 'bg-pink-600',
        'orange' => 'bg-orange-600',
        default => 'bg-gray-600',
    };
    
    $textColorClass = match($color) {
        'blue' => 'text-blue-600 dark:text-blue-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
        'green' => 'text-green-600 dark:text-green-400',
        'indigo' => 'text-indigo-600 dark:text-indigo-400',
        'cyan' => 'text-cyan-600 dark:text-cyan-400',
        'yellow' => 'text-yellow-600 dark:text-yellow-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'pink' => 'text-pink-600 dark:text-pink-400',
        'orange' => 'text-orange-600 dark:text-orange-400',
        default => 'text-gray-600 dark:text-gray-400',
    };
@endphp

@if($progress)
    <div class="flex items-center gap-3">
        <div class="flex-1">
            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span class="font-medium">{{ number_format($progress['processed']) }} / {{ number_format($progress['total']) }}</span>
                <span class="font-semibold {{ $progress['status'] === 'processing' ? $textColorClass : '' }}">
                    {{ number_format($progress['progress'], 1) }}%
                </span>
            </div>
            <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                <div class="{{ $progressBarClass }} h-2.5 rounded-full transition-all duration-500 {{ $progress['status'] === 'processing' ? 'animate-pulse' : '' }}" 
                     style="width: {{ $progress['progress'] }}%">
                </div>
                @if($progress['status'] === 'processing' && $progress['progress'] < 100)
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                @endif
            </div>
            @if($progress['status'] === 'processing')
                <div class="mt-1 text-xs {{ $textColorClass }} font-medium animate-pulse">
                    Sedang memproses...
                </div>
            @endif
        </div>
    </div>
@else
    <span class="text-xs text-gray-400 dark:text-gray-500">Belum pernah dijalankan</span>
@endif
