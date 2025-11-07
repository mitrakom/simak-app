@props([
    'title' => '',
    'value' => '',
    'icon' => null,
    'change' => null,
    'changeType' => 'neutral', // positive, negative, neutral
    'color' => 'blue', // blue, green, red, yellow, purple, pink
])

@php
    $colorClasses = [
        'blue' => 'bg-blue-500',
        'green' => 'bg-green-500',
        'red' => 'bg-red-500',
        'yellow' => 'bg-yellow-500',
        'purple' => 'bg-purple-500',
        'pink' => 'bg-pink-500',
    ];
    
    $changeClasses = [
        'positive' => 'text-green-600 dark:text-green-400',
        'negative' => 'text-red-600 dark:text-red-400',
        'neutral' => 'text-gray-600 dark:text-gray-400',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            
            @if($change)
                <p class="mt-2 text-sm {{ $changeClasses[$changeType] }}">
                    @if($changeType === 'positive')
                        <span>↑</span>
                    @elseif($changeType === 'negative')
                        <span>↓</span>
                    @endif
                    {{ $change }}
                </p>
            @endif
        </div>
        
        @if($icon)
            <div class="size-12 rounded-full {{ $colorClasses[$color] }} flex items-center justify-center text-white flex-shrink-0">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
