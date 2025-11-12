# Panduan Halaman Synchronize

## Overview
Halaman Synchronize adalah fitur untuk mengelola sinkronisasi data dari Feeder PDDIKTI ke database lokal. Halaman ini menyediakan antarmuka yang user-friendly untuk menjalankan berbagai job sinkronisasi secara individual atau bersamaan.

## Fitur Utama

### 1. **Dashboard Sinkronisasi**
- Menampilkan semua job sinkronisasi yang tersedia
- Status real-time untuk setiap job
- Progress bar untuk monitoring
- Kategorisasi job (Master, Mahasiswa, Dosen, Akademik)

### 2. **Job Management**
Terdapat 10 job sinkronisasi yang tersedia:

#### Data Master
- **Sinkronisasi Program Studi** - Menyinkronkan data program studi

#### Data Dosen
- **Sinkronisasi Dosen** - Menyinkronkan data dosen
- **Sinkronisasi Dosen Akreditasi** - Data dosen untuk keperluan akreditasi
- **Sinkronisasi Penelitian Dosen** - Data penelitian dosen

#### Data Mahasiswa
- **Sinkronisasi Mahasiswa** - Menyinkronkan data mahasiswa
  - Parameter: Angkatan (contoh: 2024 atau 2020-2024)
  - Parameter: Fetch Biodata Detail (checkbox)
- **Sinkronisasi Akademik Mahasiswa** - Data akademik mahasiswa
  - Parameter: Semester ID (contoh: 20241)
- **Sinkronisasi Aktivitas Mahasiswa** - Data aktivitas mahasiswa
- **Sinkronisasi Prestasi Mahasiswa** - Data prestasi mahasiswa
- **Sinkronisasi Lulusan** - Data lulusan

#### Data Akademik
- **Sinkronisasi Bimbingan TA** - Data bimbingan tugas akhir

### 3. **Parameter Konfigurasi**
Beberapa job memiliki parameter yang dapat dikonfigurasi:
- **Angkatan** - Filter berdasarkan tahun angkatan (mendukung range, contoh: 2020-2024)
- **Semester ID** - Filter berdasarkan semester tertentu
- **Fetch Biodata Detail** - Opsi untuk mengambil detail biodata lengkap

Parameter yang diinputkan akan disimpan sebagai nilai default untuk eksekusi berikutnya.

### 4. **Progress Monitoring**
Setiap job menampilkan:
- **Progress Bar** - Persentase penyelesaian
- **Total Records** - Jumlah total data yang akan diproses
- **Processed** - Jumlah data yang berhasil diproses
- **Failed** - Jumlah data yang gagal diproses
- **Status** - Status job (Pending, Berjalan, Selesai, Gagal)

### 5. **Expandable Details**
Klik ikon dropdown pada job untuk melihat:
- Form konfigurasi parameter
- Detail progress terakhir
- Waktu mulai dan selesai
- Durasi eksekusi

### 6. **Sinkronisasi Batch**
Tombol **"Sinkronisasi Semua"** akan menjalankan semua job secara berurutan.

## Cara Penggunaan

### Sinkronisasi Individual

1. Pilih job yang ingin dijalankan
2. Jika job memiliki parameter:
   - Klik ikon dropdown untuk expand
   - Isi parameter yang diperlukan
   - Parameter akan tersimpan otomatis
3. Klik tombol **"Sinkron"** pada baris job
4. Monitor progress melalui progress bar
5. Expand baris untuk melihat detail progress

### Sinkronisasi Semua Data

1. Pastikan parameter job sudah dikonfigurasi (jika diperlukan)
2. Klik tombol **"Sinkronisasi Semua"** di header
3. Sistem akan menjalankan semua job secara berurutan
4. Monitor progress masing-masing job

### Melihat Detail Progress

1. Klik ikon dropdown pada job yang ingin dilihat
2. Bagian expandable akan menampilkan:
   - Form parameter (jika ada)
   - Statistik lengkap (Total, Processed, Failed, Progress %)
   - Waktu eksekusi (Started, Completed, Duration)

## Auto Refresh
Halaman akan otomatis refresh setiap 5 detik untuk update status dan progress terbaru dari job yang sedang berjalan.

## Teknologi

### Backend
- **Livewire Class-based Component** - `App\Livewire\Admin\Synchronize`
- **Queue System** - Background job processing dengan Laravel Queue
- **SyncBatchProgress Model** - Tracking progress setiap job
- **SyncJobConfiguration Model** - Menyimpan konfigurasi parameter default

### Frontend
- **Tailwind CSS 4** - Styling dengan utility classes
- **Alpine.js** - Interaktivitas client-side
- **Auto-refresh** - JavaScript setInterval untuk polling updates

## File Struktur

```
app/
├── Livewire/
│   └── Admin/
│       └── Synchronize.php          # Main component logic
├── Jobs/
│   ├── SyncProdiJob.php
│   ├── SyncDosenJob.php
│   ├── SyncMahasiswaJob.php
│   └── ...                          # 20 sync jobs total
└── Models/
    ├── SyncBatchProgress.php        # Progress tracking
    └── SyncJobConfiguration.php      # Parameter storage

resources/views/livewire/admin/
└── synchronize.blade.php            # UI template

database/migrations/
└── 2025_11_08_071711_create_sync_job_configurations_table.php

tests/Feature/Livewire/Admin/
└── SynchronizeTest.php              # Component tests
```

## Testing
Jalankan test dengan:
```bash
php artisan test --filter=SynchronizeTest
```

Test coverage meliputi:
- ✅ Render halaman successfully
- ✅ Display all sync jobs
- ✅ Toggle expandable rows
- ✅ Sync individual jobs
- ✅ Sync all jobs
- ✅ Route integration

## Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
- Job scheduling dengan cron
- Email notification saat selesai
- Export progress logs
- Job history dan audit trail
- Cancel running jobs
- Retry failed jobs
- Job prioritization
- Parallel job execution

## Troubleshooting

### Job tidak berjalan
- Pastikan queue worker berjalan: `php artisan queue:work`
- Check log di `storage/logs/laravel.log`

### Progress tidak update
- Pastikan JavaScript auto-refresh aktif
- Check browser console untuk errors
- Refresh halaman secara manual

### Parameter tidak tersimpan
- Check permission database
- Verify sync_job_configurations table exists
- Check error message di UI

---

**Created**: November 2025  
**Last Updated**: November 2025  
**Version**: 1.0.0
