@props(['currentInstitusi' => null])

@php
    $user = Auth::user();
@endphp

<aside 
    x-data="{ 
        sidebarOpen: false,
        analysisOpen: false,
        reportsOpen: false,
        masterDataOpen: false
    }" 
    @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen }"
>
    <div class="flex flex-col h-full">
        <!-- Logo & Institusi -->
        <div class="h-16 px-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 h-full">
                <div class="size-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                    <x-icon name="chart" size="5" class="text-white" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-base font-bold text-gray-900 dark:text-white truncate">SIMAK</div>
                    @if(isset($currentInstitusi))
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $currentInstitusi->nama }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" 
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                <x-icon name="home" size="5" />
                Dashboard
            </a>

            <!-- Users -->
            <a href="{{ route('admin.users', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" 
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                <x-icon name="users" size="5" />
                Users
            </a>

            <!-- Analytics -->
            <a href="{{ route('admin.analytics', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" 
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.analytics*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                <x-icon name="chart" size="5" />
                Analytics
            </a>

            <!-- Synchronize -->
            <a href="{{ route('admin.synchronize', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" 
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.synchronize*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                <x-icon name="refresh" size="5" />
                Synchronize
            </a>

            <!-- Divider -->
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p>
            </div>

            <!-- Analisis Akademik (dengan Submenu) -->
            <div>
                <button 
                    @click="analysisOpen = !analysisOpen"
                    class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <x-icon name="cube" size="5" />
                        <span>Analisis Akademik</span>
                    </div>
                    <div class="transition-transform duration-200" :class="analysisOpen ? 'rotate-180' : ''">
                        <x-icon name="chevron-down" size="4" />
                    </div>
                </button>
                
                <!-- Submenu Analisis Akademik -->
                <div 
                    x-show="analysisOpen"
                    x-collapse
                    class="mt-1 ml-8 space-y-1"
                >
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Peta Perjalanan
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Sebaran IPS
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Monitoring Bimbingan
                    </a>
                </div>
            </div>

            <!-- Laporan Strategis (dengan Submenu) -->
            <div>
                <button 
                    @click="reportsOpen = !reportsOpen"
                    class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <x-icon name="document-chart" size="5" />
                        <span>Laporan Strategis</span>
                    </div>
                    <div class="transition-transform duration-200" :class="reportsOpen ? 'rotate-180' : ''">
                        <x-icon name="chevron-down" size="4" />
                    </div>
                </button>
                
                <!-- Submenu Laporan Strategis -->
                <div 
                    x-show="reportsOpen"
                    x-collapse
                    class="mt-1 ml-8 space-y-1"
                >
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Kesiapan Akreditasi
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Pelaporan Prodi
                    </a>
                </div>
            </div>

            <!-- Data Master (dengan Submenu) -->
            <div>
                <button 
                    @click="masterDataOpen = !masterDataOpen"
                    class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <x-icon name="database" size="5" />
                        <span>Data Master</span>
                    </div>
                    <div class="transition-transform duration-200" :class="masterDataOpen ? 'rotate-180' : ''">
                        <x-icon name="chevron-down" size="4" />
                    </div>
                </button>
                
                <!-- Submenu Data Master -->
                <div 
                    x-show="masterDataOpen"
                    x-collapse
                    class="mt-1 ml-8 space-y-1"
                >
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Prodi
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Mahasiswa
                    </a>
                    <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                        <x-icon name="arrow-right" size="4" />
                        Dosen
                    </a>
                </div>
            </div>

            <!-- Divider -->
            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>
            </div>

            <!-- Settings -->
            <a href="{{ route('admin.settings', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" 
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.settings*') ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                <x-icon name="cog" size="5" />
                Settings
            </a>
        </nav>

        <!-- User Profile -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                @php
                    $initials = $user ? strtoupper(substr($user->name, 0, 2)) : 'AD';
                @endphp
                <div class="size-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $user->name ?? 'Admin User' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email ?? 'admin@example.com' }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div 
    x-data="{ open: false }"
    @toggle-sidebar.window="open = !open"
    x-show="open" 
    @click="open = false"
    class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
></div>
