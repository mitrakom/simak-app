<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Dark Mode Toggle Button (Fixed Position) -->
    <button 
        @click="darkMode = !darkMode"
        class="fixed top-4 right-4 p-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-700 transition-all z-50"
        title="Toggle dark mode"
    >
        <!-- Sun Icon (show in dark mode) -->
        <svg x-show="darkMode" class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <!-- Moon Icon (show in light mode) -->
        <svg x-show="!darkMode" class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-4xl w-full text-center">
            <div class="inline-flex items-center justify-center size-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-lg mb-6">
                <svg class="size-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-4">Welcome to SIMAK App</h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-12">Admin Dashboard dengan Laravel Livewire & Tailwind CSS</p>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-xl border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Fitur yang Tersedia</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Reusable Components</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Card, Button, Badge, Alert, dll</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Livewire Volt</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Dashboard & Users management</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Dark Mode Ready</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Support dark mode otomatis</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="size-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Dokumentasi Lengkap</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Panduan di COMPONENT_GUIDE.md</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
