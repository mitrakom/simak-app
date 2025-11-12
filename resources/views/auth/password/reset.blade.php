<x-layouts.landing>
    <x-slot name="title">Reset Password - {{ $institusi->nama }} | SIMAK</x-slot>
    <x-slot name="institutionName">{{ $institusi->nama }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-green-600 to-blue-600 rounded-2xl mb-4">
                    <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Buat Password Baru
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Masukkan password baru untuk akun Anda
                </p>
            </div>

            <!-- Reset Card -->
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

                    <!-- Reset Form -->
                    <form method="POST" action="{{ route('auth.password.reset', ['institusi' => $institusi->slug, 'token' => $token]) }}" class="space-y-6">
                        @csrf

                        <!-- Hidden Token -->
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Email Address (readonly) -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email
                            </label>
                            <x-input 
                                id="email" 
                                type="email" 
                                name="email" 
                                :value="old('email', $email ?? '')" 
                                required 
                                readonly
                                class="bg-gray-50 dark:bg-gray-700"
                                autocomplete="username"
                            />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Password Baru
                            </label>
                            <x-input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                autofocus
                                placeholder="Minimal 8 karakter"
                                autocomplete="new-password"
                            />
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Password minimal 8 karakter, kombinasi huruf, angka, dan simbol.
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Konfirmasi Password Baru
                            </label>
                            <x-input 
                                id="password_confirmation" 
                                type="password" 
                                name="password_confirmation" 
                                required 
                                placeholder="Masukkan ulang password baru"
                                autocomplete="new-password"
                            />
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="flex items-start gap-3">
                                <svg class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900 dark:text-blue-300 mb-1">
                                        Tips Password Kuat:
                                    </p>
                                    <ul class="space-y-1 text-blue-800 dark:text-blue-400">
                                        <li>• Minimal 8 karakter</li>
                                        <li>• Gunakan kombinasi huruf besar & kecil</li>
                                        <li>• Sertakan angka dan simbol</li>
                                        <li>• Hindari kata-kata umum</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <x-button type="submit" variant="primary" class="w-full justify-center">
                                <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Reset Password
                            </x-button>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-8 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-center text-gray-600 dark:text-gray-400">
                        Ingat password Anda? 
                        <a href="{{ route('auth.login.form', ['institusi' => $institusi->slug]) }}" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            Kembali ke Login
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
