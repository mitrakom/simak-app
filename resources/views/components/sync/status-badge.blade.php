@props([
    'status' => 'pending',
    'showIcon' => true,
])

@php
$config = match($status) {
    'completed' => [
        'bg' => 'bg-green-100 dark:bg-green-900/20',
        'text' => 'text-green-700 dark:text-green-400',
        'icon' => 'check-circle',
        'label' => 'Selesai',
    ],
    'processing' => [
        'bg' => 'bg-blue-100 dark:bg-blue-900/20',
        'text' => 'text-blue-700 dark:text-blue-400',
        'icon' => 'spinner',
        'label' => 'Berjalan',
        'animate' => true,
    ],
    'failed' => [
        'bg' => 'bg-red-100 dark:bg-red-900/20',
        'text' => 'text-red-700 dark:text-red-400',
        'icon' => 'x-circle',
        'label' => 'Gagal',
    ],
    'cancelled' => [
        'bg' => 'bg-gray-100 dark:bg-gray-900/20',
        'text' => 'text-gray-700 dark:text-gray-400',
        'icon' => 'x-circle',
        'label' => 'Dibatalkan',
    ],
    default => [
        'bg' => 'bg-yellow-100 dark:bg-yellow-900/20',
        'text' => 'text-yellow-700 dark:text-yellow-400',
        'icon' => 'clock',
        'label' => 'Pending',
    ],
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full {$config['bg']} {$config['text']}"]) }}>
    @if($showIcon)
        <x-icon 
            :name="$config['icon']" 
            size="3" 
            :class="isset($config['animate']) ? 'animate-spin' : ''" 
        />
    @endif
    {{ $config['label'] }}
</span>
