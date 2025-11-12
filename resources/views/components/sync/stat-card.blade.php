@props([
    'label',
    'value',
    'icon' => 'clipboard',
    'color' => 'gray',
    'animate' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4']) }}>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</p>
            <p class="text-2xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400 mt-1">{{ $value }}</p>
        </div>
        <div class="p-3 bg-{{ $color }}-100 dark:bg-{{ $color }}-900/20 rounded-full">
            <x-icon :name="$icon" size="6" :class="$animate ? 'animate-spin' : ''" class="text-{{ $color }}-600 dark:text-{{ $color }}-400" />
        </div>
    </div>
</div>
