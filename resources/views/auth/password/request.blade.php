<x-layouts.landing>
    <x-slot name="title">Reset Password - {{ $institusi->nama }} | SIMAK</x-slot>
    <x-slot name="institutionName">{{ $institusi->nama }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl mb-4">
                    <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Lupa Password?
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Masukkan email Anda untuk menerima link reset password
                </p>
            </div>

            <!-- Request Card -->
            <x-card :padding="false" class="overflow-hidden">
                <div class="p-8">
                    <!-- Session Status -->
                    @if (session('success'))
                        <x-alert variant="success" :dismissible="true" class="mb-6">
                            {{ session('success') }}
                        </x-alert>
                        
                        @if (session('reset_link'))
                            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                                    Link Reset Password (Development Mode):
                                </p>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="text" 
                                        readonly 
                                        value="{{ session('reset_link') }}" 
                                        class="flex-1 text-xs bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded px-3 py-2 text-gray-900 dark:text-white"
                                        id="reset-link-input"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="copyResetLink()" 
                                        class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs font-medium"
                                    >
                                        Copy
                                    </button>
                                </div>
                                <p class="text-xs text-blue-700 dark:text-blue-400 mt-2">
                                    Di production, link ini akan dikirim via email.
                                </p>
                            </div>

                            <script>
                                function copyResetLink() {
                                    const input = document.getElementById('reset-link-input');
                                    input.select();
                                    document.execCommand('copy');
                                    alert('Link berhasil di-copy!');
                                }
                            </script>
                        @endif
                    @endif

                    @if (session('error'))
                        <x-alert variant="danger" :dismissible="true" class="mb-6">
                            {{ session('error') }}
                        </x-alert>
                    @endif

                    <!-- Info Box -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex gap-3">
                            <svg class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Masukkan alamat email yang terdaftar di {{ $institusi->nama }}. Kami akan mengirimkan link untuk mereset password Anda.
                            </p>
                        </div>
                    </div>

                    <!-- Request Form -->
                    <form method="POST" action="{{ route('auth.password.send-link', ['institusi' => $institusi->slug]) }}" class="space-y-6">
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
                                autocomplete="email"
                            />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <x-button type="submit" variant="primary" class="w-full justify-center">
                                <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Kirim Link Reset Password
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
