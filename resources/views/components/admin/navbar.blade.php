@props(['currentInstitusi' => null])

@php
    $user = Auth::user();
    $initials = $user ? strtoupper(substr($user->name, 0, 2)) : 'AD';
@endphp

<header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- Mobile Menu Button -->
        <button 
            @click="$dispatch('toggle-sidebar')"
            class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
            <x-icon name="menu-bars" size="6" />
        </button>

        <!-- Search Bar -->
        <div class="hidden md:flex flex-1 max-w-md">
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-icon name="search" size="5" class="text-gray-400" />
                </div>
                <input 
                    type="search" 
                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                    placeholder="Search..."
                >
            </div>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-2 ml-auto">
            <!-- Dark Mode Toggle -->
            <button 
                @click="darkMode = !darkMode"
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Toggle dark mode"
            >
                <x-icon name="sun" size="5" x-show="darkMode" />
                <x-icon name="moon" size="5" x-show="!darkMode" />
            </button>

            <!-- Notifications -->
            <div class="relative" x-data="{ open: false }">
                <button 
                    @click="open = !open"
                    class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-icon name="bell" size="5" />
                    <span class="absolute top-1 right-1 size-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Notifications Dropdown -->
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
                    style="display: none;"
                >
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        <a href="#" class="flex gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="size-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <x-icon name="user" size="5" class="text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">New user registered</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">John Doe just signed up</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">5 minutes ago</p>
                            </div>
                        </a>
                        <a href="#" class="flex gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="size-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <x-icon name="check-circle" size="5" class="text-green-600 dark:text-green-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order completed</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Order #1234 has been delivered</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">2 hours ago</p>
                            </div>
                        </a>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 text-center">
                        <a href="#" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="relative" x-data="{ open: false }">
                <button 
                    @click="open = !open"
                    class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="size-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        {{ $initials }}
                    </div>
                    <x-icon name="chevron-down" size="4" class="text-gray-500 dark:text-gray-400 hidden md:block" />
                </button>

                <!-- User Dropdown -->
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
                    style="display: none;"
                >
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name ?? 'Admin User' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email ?? 'admin@example.com' }}</p>
                    </div>
                    <div class="py-1">
                        <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <x-icon name="user" size="4" />
                            Profile
                        </a>
                        <a href="{{ route('admin.settings', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <x-icon name="cog" size="4" />
                            Settings
                        </a>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 py-1">
                        <form method="POST" action="{{ route('auth.logout', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <x-icon name="logout" size="4" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
