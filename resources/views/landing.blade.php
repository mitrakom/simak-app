<x-layouts.landing>
    <x-slot name="title">{{ $institusi->nama }} - SIMAK</x-slot>
    <x-slot name="description">Sistem Informasi Monitoring Akademik untuk {{ $institusi->nama }} - Transformasi data PDDikti menjadi keputusan strategis</x-slot>
    <x-slot name="institutionName">{{ $institusi->nama }}</x-slot>
    <x-slot name="institutionSlug">{{ $institusi->slug }}</x-slot>

    <x-slot name="navActions">
        <x-button href="{{ route('admin.dashboard', $institusi) }}" variant="primary" size="sm">
            Masuk Dashboard
        </x-button>
    </x-slot>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="absolute inset-0 bg-grid-gray-900/[0.04] dark:bg-grid-white/[0.02] bg-[size:20px_20px]"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-32">
            <div class="text-center">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full text-sm font-medium mb-6">
                    <span class="relative flex size-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full size-2 bg-blue-600"></span>
                    </span>
                    {{ $institusi->nama }}
                </div>

                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    Transformasi Data PDDikti<br>
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        Menjadi Keputusan Strategis
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-10">
                    SIMAK adalah platform dasbor analitik canggih yang mengubah data kompleks PDDikti Feeder Anda menjadi laporan visual yang interaktif. Dirancang khusus untuk Pimpinan dan Ketua Program Studi.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <x-button href="{{ route('admin.dashboard', $institusi) }}" variant="primary" size="lg">
                        <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Mulai Sekarang
                    </x-button>
                    <x-button href="#fitur" variant="secondary" size="lg" :outline="true">
                        <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pelajari Lebih Lanjut
                    </x-button>
                </div>

                <!-- Stats -->
                <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-3xl mx-auto">
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">Real-time</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Monitoring Data</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">100%</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Otomatis</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-bold text-green-600 dark:text-green-400 mb-2">Akurat</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Berbasis Data</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fitur" class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Fitur Unggulan
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Platform lengkap untuk monitoring dan analisis akademik yang membantu pengambilan keputusan strategis
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <x-card class="hover:shadow-lg transition-shadow duration-300">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mb-6">
                            <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                            Pantau Kinerja Prodi
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Monitor kesehatan akademik, tren pendaftaran, dan rasio dosen:mahasiswa secara real-time dengan visualisasi data yang intuitif.
                        </p>
                    </div>
                </x-card>

                <!-- Feature 2 -->
                <x-card class="hover:shadow-lg transition-shadow duration-300">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl mb-6">
                            <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                            Analisis Mahasiswa Mendalam
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Lacak progres "Peta Perjalanan Mahasiswa" dengan tren IPK & masa studi, dan identifikasi mahasiswa berisiko drop out lebih dini.
                        </p>
                    </div>
                </x-card>

                <!-- Feature 3 -->
                <x-card class="hover:shadow-lg transition-shadow duration-300">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center size-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl mb-6">
                            <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                            Percepat Akreditasi & IKU
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Dapatkan data matang yang siap digunakan untuk pelaporan Indikator Kinerja Utama (IKU) dan kebutuhan borang akreditasi secara otomatis.
                        </p>
                    </div>
                </x-card>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="manfaat" class="py-20 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        Kekuatan Analisis Akademik di Ujung Jari Anda
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                        SIMAK memberikan Anda kontrol penuh atas data akademik institusi dengan dashboard interaktif yang mudah dipahami.
                    </p>

                    <!-- Benefits List -->
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 size-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <svg class="size-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Pengambilan Keputusan Cepat</h4>
                                <p class="text-gray-600 dark:text-gray-400">Visualisasi data real-time membantu Anda membuat keputusan berdasarkan fakta, bukan asumsi.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 size-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <svg class="size-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Hemat Waktu & Tenaga</h4>
                                <p class="text-gray-600 dark:text-gray-400">Otomasi pelaporan mengurangi waktu kerja manual hingga 80%, fokus pada strategi bukan administrasi.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 size-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <svg class="size-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Integrasi PDDikti Seamless</h4>
                                <p class="text-gray-600 dark:text-gray-400">Sinkronisasi otomatis dengan PDDikti Feeder tanpa perlu input manual atau duplikasi data.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 size-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mt-1">
                                <svg class="size-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Dashboard yang User-Friendly</h4>
                                <p class="text-gray-600 dark:text-gray-400">Interface intuitif yang tidak memerlukan training khusus, langsung pakai dan produktif.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Visual Element -->
                <div class="relative">
                    <div class="relative bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 shadow-2xl">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 space-y-4">
                            <!-- Mock Dashboard Preview -->
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Dashboard Overview</div>
                                <x-badge variant="success" :dot="true">Live</x-badge>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">2,450</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Total Mahasiswa</div>
                                </div>
                                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">98%</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Akurasi Data</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">3.45</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Rata-rata IPK</div>
                                </div>
                                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">156</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Dosen Aktif</div>
                                </div>
                            </div>

                            <div class="h-32 bg-gradient-to-r from-blue-100 to-purple-100 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg flex items-end justify-around p-4">
                                <div class="w-8 bg-blue-500 rounded-t" style="height: 60%"></div>
                                <div class="w-8 bg-purple-500 rounded-t" style="height: 80%"></div>
                                <div class="w-8 bg-blue-500 rounded-t" style="height: 70%"></div>
                                <div class="w-8 bg-purple-500 rounded-t" style="height: 90%"></div>
                                <div class="w-8 bg-blue-500 rounded-t" style="height: 75%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 size-20 bg-yellow-400 rounded-full blur-2xl opacity-50"></div>
                    <div class="absolute -bottom-4 -left-4 size-20 bg-pink-400 rounded-full blur-2xl opacity-50"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-br from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Siap Membaca Masa Depan Kampus Anda Melalui Data?
            </h2>
            <p class="text-lg text-blue-100 mb-8">
                Bergabunglah dengan institusi pendidikan yang telah menggunakan SIMAK untuk transformasi digital akademik mereka.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <x-button href="{{ route('admin.dashboard', $institusi) }}" variant="secondary" size="lg">
                    <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Akses Dashboard Sekarang
                </x-button>
                <a href="mailto:info@simak.id" class="inline-flex items-center px-8 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg border-2 border-white/50 transition-colors">
                    <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>
</x-layouts.landing>
