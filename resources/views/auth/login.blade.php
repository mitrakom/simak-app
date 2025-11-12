<x-layouts.landing>
    <x-slot name="title">Login - {{ $institusi->nama }} | SIMAK</x-slot>
    <x-slot name="institutionName">{{ $institusi->nama }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl mb-4">
                    <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Selamat Datang
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Login ke dashboard {{ $institusi->nama }}
                </p>
            </div>

            <!-- Login Card -->
            <x-card :padding="false" class="overflow-hidden">
                <div class="p-8">
                    <!-- Session Status -->
                    @if (session('success'))
                        <x-alert variant="success" :dismissible="true" class="mb-6">
                            {{ session('success') }}
                        </x-alert>
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger" :dismissible="true" class="mb-6">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('auth.login', ['institusi' => $institusi->slug]) }}" class="space-y-6">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email
                            </label>
                            <x-input 
                                id="email" 
                                type="email" 
                                name="email" 
                                :value="old('email')" 
                                required 
                                autofocus 
                                placeholder="nama@institusi.ac.id"
                                autocomplete="username"
                            />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password
                            </label>
                            <x-input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                placeholder="••••••••"
                                autocomplete="current-password"
                            />
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input 
                                    id="remember" 
                                    name="remember" 
                                    type="checkbox" 
                                    class="size-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Ingat saya
                                </label>
                            </div>

                            <a href="{{ route('auth.password.request', ['institusi' => $institusi->slug]) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                Lupa password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <x-button type="submit" variant="primary" class="w-full justify-center">
                                <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Login
                            </x-button>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-8 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-center text-gray-600 dark:text-gray-400">
                        Belum punya akses? 
                        <a href="mailto:admin@{{ $institusi->slug }}.ac.id" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            Hubungi Administrator
                        </a>
                    </p>
                </div>
            </x-card>

            <!-- Back to Landing -->
            <div class="mt-6 text-center">
                <a href="{{ route('landing', ['institusi' => $institusi->slug]) }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <svg class="size-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Landing Page
                </a>
            </div>
        </div>
    </div>
</x-layouts.landing>
