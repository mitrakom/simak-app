@volt
<?php

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

state([
    'currentInstitusi' => null,
    'theme_primary_color' => 'blue',
    'theme_secondary_color' => 'purple',
    'theme_accent_color' => 'indigo',
    'theme_mode' => 'auto',
    'custom_css' => '',
    'successMessage' => '',
    'errorMessage' => '',
]);

mount(function () {
    $institusiSlug = request()->route('institusi')->slug ?? auth()->user()->institusi->slug;
    $this->currentInstitusi = \App\Models\Institusi::where('slug', $institusiSlug)->firstOrFail();

    $this->theme_primary_color = $this->currentInstitusi->theme_primary_color ?? 'blue';
    $this->theme_secondary_color = $this->currentInstitusi->theme_secondary_color ?? 'purple';
    $this->theme_accent_color = $this->currentInstitusi->theme_accent_color ?? 'indigo';
    $this->theme_mode = $this->currentInstitusi->theme_mode ?? 'auto';
    $this->custom_css = $this->currentInstitusi->custom_css ?? '';
});

$availableColors = computed(fn () => \App\Models\Institusi::getAvailableColors());

$updateTheme = function () {
    try {
        $this->currentInstitusi->update([
            'theme_primary_color' => $this->theme_primary_color,
            'theme_secondary_color' => $this->theme_secondary_color,
            'theme_accent_color' => $this->theme_accent_color,
            'theme_mode' => $this->theme_mode,
            'custom_css' => $this->custom_css,
        ]);

        $this->successMessage = 'Theme updated successfully! Refreshing...';
        $this->dispatch('theme-updated');
    } catch (\Exception $e) {
        $this->errorMessage = 'Failed to update theme: '.$e->getMessage();
    }
};

$resetTheme = function () {
    $this->mount();
    $this->successMessage = '';
    $this->errorMessage = '';
};

?>

<x-layouts.admin>
    <x-slot name="header">Theme Settings</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Success/Error Messages -->
        @if($successMessage)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-sm text-green-800 dark:text-green-200">{{ $successMessage }}</p>
            </div>
        @endif

        @if($errorMessage)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-sm text-red-800 dark:text-red-200">{{ $errorMessage }}</p>
            </div>
        @endif

        <!-- Theme Settings Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Customize Your Theme</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Choose colors and appearance for {{ $currentInstitusi->nama }}</p>
            </div>

            <div class="p-6 space-y-8">
                <!-- Theme Preview -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Preview</h3>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <div class="bg-{{ $theme_primary_color }}-500 h-24 rounded-lg flex items-center justify-center text-white font-semibold shadow-lg">
                                    Primary
                                </div>
                                <p class="mt-2 text-xs text-center text-gray-600 dark:text-gray-400">Main Color</p>
                            </div>
                            <div>
                                <div class="bg-{{ $theme_secondary_color }}-500 h-24 rounded-lg flex items-center justify-center text-white font-semibold shadow-lg">
                                    Secondary
                                </div>
                                <p class="mt-2 text-xs text-center text-gray-600 dark:text-gray-400">Supporting Color</p>
                            </div>
                            <div>
                                <div class="bg-{{ $theme_accent_color }}-500 h-24 rounded-lg flex items-center justify-center text-white font-semibold shadow-lg">
                                    Accent
                                </div>
                                <p class="mt-2 text-xs text-center text-gray-600 dark:text-gray-400">Highlight Color</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Primary Color Picker -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Primary Color
                    </label>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-11 gap-3">
                        @foreach($this->availableColors as $key => $color)
                            <button
                                type="button"
                                wire:click="$set('theme_primary_color', '{{ $key }}')"
                                class="relative {{ $color['class'] }} h-12 rounded-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 {{ $theme_primary_color === $key ? 'ring-4 ring-offset-2 ring-gray-900 dark:ring-gray-100 scale-110' : '' }}"
                                title="{{ $color['name'] }}"
                            >
                                @if($theme_primary_color === $key)
                                    <svg class="absolute inset-0 m-auto size-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Secondary Color Picker -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Secondary Color
                    </label>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-11 gap-3">
                        @foreach($this->availableColors as $key => $color)
                            <button
                                type="button"
                                wire:click="$set('theme_secondary_color', '{{ $key }}')"
                                class="relative {{ $color['class'] }} h-12 rounded-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 {{ $theme_secondary_color === $key ? 'ring-4 ring-offset-2 ring-gray-900 dark:ring-gray-100 scale-110' : '' }}"
                                title="{{ $color['name'] }}"
                            >
                                @if($theme_secondary_color === $key)
                                    <svg class="absolute inset-0 m-auto size-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Accent Color Picker -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Accent Color
                    </label>
                    <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-11 gap-3">
                        @foreach($this->availableColors as $key => $color)
                            <button
                                type="button"
                                wire:click="$set('theme_accent_color', '{{ $key }}')"
                                class="relative {{ $color['class'] }} h-12 rounded-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 {{ $theme_accent_color === $key ? 'ring-4 ring-offset-2 ring-gray-900 dark:ring-gray-100 scale-110' : '' }}"
                                title="{{ $color['name'] }}"
                            >
                                @if($theme_accent_color === $key)
                                    <svg class="absolute inset-0 m-auto size-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Theme Mode -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Theme Mode
                    </label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="theme_mode" value="light" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="size-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Light</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="theme_mode" value="dark" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="size-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Dark</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="theme_mode" value="auto" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="size-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Auto</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Custom CSS -->
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Custom CSS <span class="text-xs font-normal text-gray-500">(Advanced)</span>
                    </label>
                    <textarea 
                        wire:model="custom_css"
                        rows="6"
                        placeholder="/* Add your custom CSS here */"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add custom CSS to further customize your theme. Use with caution.</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a 
                        href="{{ route('admin.settings', ['institusi' => $currentInstitusi->slug]) }}"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        ← Back to Settings
                    </a>
                    <div class="flex gap-3">
                        <button 
                            type="button"
                            wire:click="resetTheme"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
                        >
                            Reset
                        </button>
                        <button 
                            type="button"
                            wire:click="updateTheme"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                        >
                            Save Theme
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('theme-updated', () => {
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
    </script>
    @endscript
</x-layouts.admin>
@endvolt
