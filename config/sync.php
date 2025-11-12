<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Sync Job Parameters
    |--------------------------------------------------------------------------
    |
    | Default parameter values untuk setiap sync job. Parameter ini bisa
    | disesuaikan melalui interface web atau langsung di file ini.
    |
    */

    'prodi' => [
        // Tidak ada parameter tambahan
    ],

    'dosen' => [
        // Tidak ada parameter tambahan
    ],

    'mahasiswa' => [
        // Filter angkatan mahasiswa
        // Format: '2024' untuk single year atau '2020-2024' untuk range
        'angkatan' => env('SYNC_MAHASISWA_ANGKATAN', '2024'),

        // Fetch detail biodata mahasiswa (lebih lambat tapi data lengkap)
        'fetch_biodata_detail' => env('SYNC_MAHASISWA_FETCH_BIODATA', false),
    ],

    'akademik_mahasiswa' => [
        // Filter semester
        // Format: '20241' (tahun + semester: 1=Ganjil, 2=Genap)
        'semester' => env('SYNC_AKADEMIK_SEMESTER', '20241'),
    ],

    'lulusan' => [
        // Filter tahun lulus
        // Format: '2024' untuk single year atau '2020-2024' untuk range
        'tahun_lulus' => env('SYNC_LULUSAN_TAHUN', '2024'),
    ],

    'dosen_akreditasi' => [
        // Filter tahun ajaran untuk data akreditasi
        'tahun_ajaran' => env('SYNC_DOSEN_AKREDITASI_TAHUN', '2024'),
    ],

    'bimbingan_ta' => [
        // Filter tahun ajaran untuk bimbingan tugas akhir
        'tahun_ajaran' => env('SYNC_BIMBINGAN_TAHUN', '2024'),
    ],

    'prestasi_mahasiswa' => [
        // Filter tahun prestasi
        'tahun' => env('SYNC_PRESTASI_TAHUN', '2024'),
    ],

    'aktivitas_mahasiswa' => [
        // Filter semester untuk aktivitas (MBKM, KKN, dll)
        // Format: '20241' (tahun + semester: 1=Ganjil, 2=Genap)
        'semester' => env('SYNC_AKTIVITAS_SEMESTER', '20241'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk queue sync jobs
    |
    */

    'queue' => [
        // Queue name untuk sync jobs
        'name' => env('SYNC_QUEUE_NAME', 'default'),

        // Connection untuk queue
        'connection' => env('SYNC_QUEUE_CONNECTION', 'database'),

        // Batch size untuk record jobs
        'batch_size' => env('SYNC_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout & Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi timeout dan retry untuk sync jobs
    |
    */

    'timeout' => [
        // Timeout untuk main job (dalam detik)
        'main_job' => env('SYNC_TIMEOUT_MAIN', 300), // 5 minutes

        // Timeout untuk record job (dalam detik)
        'record_job' => env('SYNC_TIMEOUT_RECORD', 60), // 1 minute
    ],

    'retry' => [
        // Jumlah percobaan ulang untuk failed jobs
        'tries' => env('SYNC_RETRY_TRIES', 1),

        // Delay antara retry (dalam detik)
        'delay' => env('SYNC_RETRY_DELAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Jadwal otomatis untuk sync jobs (cron expression)
    | Set null untuk disable auto-sync
    |
    */

    'schedule' => [
        'prodi' => env('SYNC_SCHEDULE_PRODI', null), // e.g., '0 2 * * *' (daily at 2 AM)
        'dosen' => env('SYNC_SCHEDULE_DOSEN', null),
        'mahasiswa' => env('SYNC_SCHEDULE_MAHASISWA', null),
        'akademik_mahasiswa' => env('SYNC_SCHEDULE_AKADEMIK', null),
        'lulusan' => env('SYNC_SCHEDULE_LULUSAN', null),
        'dosen_akreditasi' => env('SYNC_SCHEDULE_DOSEN_AKREDITASI', null),
        'bimbingan_ta' => env('SYNC_SCHEDULE_BIMBINGAN', null),
        'prestasi_mahasiswa' => env('SYNC_SCHEDULE_PRESTASI', null),
        'aktivitas_mahasiswa' => env('SYNC_SCHEDULE_AKTIVITAS', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi notifikasi untuk sync completion/failure
    |
    */

    'notifications' => [
        // Enable notifikasi
        'enabled' => env('SYNC_NOTIFICATIONS_ENABLED', false),

        // Email untuk notifikasi
        'email' => env('SYNC_NOTIFICATIONS_EMAIL', null),

        // Notifikasi hanya untuk failed jobs
        'only_failures' => env('SYNC_NOTIFICATIONS_ONLY_FAILURES', true),
    ],

];
