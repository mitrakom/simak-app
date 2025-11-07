@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->only('class') }}>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <select 
        {{ $attributes->except('class')->merge([
            'class' => 'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 transition-colors ' . 
                       ($error 
                           ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                           : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20 dark:bg-gray-700 dark:text-white')
        ]) }}
    >
        {{ $slot }}
    </select>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
