<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'SIMAK') }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $description ?? 'Sistem Informasi Monitoring Akademik - Platform dasbor analitik canggih untuk transformasi data PDDikti' }}">
    <meta name="keywords" content="SIMAK, PDDikti, Monitoring Akademik, Analitik Kampus, Dashboard Pendidikan">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Theme Styles -->
    @if(isset($institutionSlug))
        <x-theme-styles />
    @endif
    
    @livewireStyles
</head>
<body class="bg-white dark:bg-gray-900 antialiased">
    <!-- Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <div class="size-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="size-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">SIMAK</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $institutionName ?? 'Monitoring Akademik' }}</p>
                    </div>
                </div>

                <!-- Navigation Links & Actions -->
                <div class="flex items-center gap-4">
                    @if(isset($navLinks))
                        <div class="hidden md:flex items-center gap-6">
                            {{ $navLinks }}
                        </div>
                    @endif

                    <!-- Dark Mode Toggle -->
                    <button 
                        @click="darkMode = !darkMode"
                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        title="Toggle dark mode"
                    >
                        <svg x-show="darkMode" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="!darkMode" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @if(isset($navActions))
                        {{ $navActions }}
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tentang SIMAK</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Sistem Informasi Monitoring Akademik untuk transformasi data PDDikti menjadi keputusan strategis berbasis analitik.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tautan Cepat</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#fitur" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">Fitur</a></li>
                        <li><a href="#manfaat" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">Manfaat</a></li>
                        @if(isset($institutionSlug))
                            <li><a href="{{ route('admin.dashboard', $institutionSlug) }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">Dashboard</a></li>
                        @endif
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Kontak</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $institutionName ?? 'Institusi' }}<br>
                        Email: info@simak.id<br>
                        © {{ date('Y') }} SIMAK. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
