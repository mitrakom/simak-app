# Progress Monitoring - Halaman Synchronize

## Overview
Sistem progress monitoring real-time untuk memantau status sinkronisasi data dari Feeder PDDIKTI.

## Fitur Progress Monitoring

### 1. **Auto-Refresh Real-Time** ⏱️
- ✅ Polling otomatis setiap **5 detik** menggunakan Livewire `wire:poll`
- ✅ Tidak perlu refresh manual
- ✅ Indikator visual "Auto-refresh aktif" dengan dot hijau berkedip

### 2. **Statistics Dashboard** 📊
Menampilkan overview semua jobs dalam 4 card:

#### Total Jobs
- Jumlah total job sinkronisasi yang tersedia
- Icon: Clipboard

#### Sedang Berjalan (Running)
- Jumlah job yang sedang diproses
- Icon: Refresh (berputar jika ada job running)
- Warna: Biru

#### Selesai (Completed)  
- Jumlah job yang sudah selesai
- Icon: Check Circle
- Warna: Hijau

#### Pending
- Jumlah job yang belum pernah dijalankan
- Icon: Clock
- Warna: Kuning

### 3. **Progress Bar per Job** 📈

#### Visual Indicators:
- **Progress Bar** dengan animasi:
  - Warna sesuai kategori job (blue, green, purple, dll)
  - Animasi `pulse` saat job sedang berjalan
  - Animasi `shimmer` (gelombang cahaya) untuk job aktif
  
- **Persentase Progress**:
  - Format: "0.0%" dengan 1 desimal
  - Bold & warna biru saat processing
  
- **Counter Records**:
  - Format: "1,234 / 5,678" dengan thousand separator
  - Update real-time setiap refresh

- **Status Text**:
  - "Sedang memproses..." (biru, berkedip) untuk job aktif

### 4. **Status Badges** 🏷️

#### Status Selesai (Completed)
```
✓ Selesai
```
- Background: Hijau muda
- Icon: Checkmark dalam circle
- Text: Hijau

#### Status Berjalan (Processing)
```
⟲ Berjalan
```
- Background: Biru muda  
- Icon: Spinner berputar
- Text: Biru
- Animasi: Continuous rotation

#### Status Gagal (Failed)
```
✕ Gagal
```
- Background: Merah muda
- Icon: X dalam circle
- Text: Merah

#### Status Pending
```
○ Pending
```
- Background: Kuning muda
- Text: Kuning

### 5. **Detail Progress (Expandable)** 📋

Saat expand row, menampilkan:

#### Parameter Configuration
- Form input untuk job yang memiliki parameter
- Auto-save ke database

#### Progress Statistics (4 Cards):
1. **Total Records** - Total data yang akan diproses
2. **Processed** - Data yang berhasil (hijau)
3. **Failed** - Data yang gagal (merah)  
4. **Progress** - Persentase (warna sesuai job)

#### Timeline Information:
- **Dimulai**: Timestamp mulai job
- **Selesai**: Timestamp selesai (jika sudah selesai)
- **Durasi**: Waktu yang dibutuhkan (human readable)

## Teknologi

### Backend
```php
// Method untuk get progress
public function getJobProgress(string $jobId): ?array

// Method untuk refresh all
public function refreshAllProgress(): void

// Method untuk statistik overview
public function getOverallStats(): array
```

### Frontend
```blade
<!-- Wire Poll untuk auto-refresh -->
<div wire:poll.5s="refreshAllProgress">

<!-- Progress bar dengan animasi -->
<div class="animate-pulse">
  <div class="animate-shimmer"></div>
</div>
```

### CSS Animations
```css
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
```

## Cara Kerja

### 1. Polling Mechanism
```
User opens page
    ↓
Component loads
    ↓
wire:poll.5s triggered
    ↓
Call refreshAllProgress()
    ↓
Component re-renders
    ↓
UI updates with latest data
    ↓
Wait 5 seconds
    ↓
Repeat...
```

### 2. Data Flow
```
Queue Worker processes job
    ↓
Updates SyncBatchProgress model
    - processed_records++
    - progress_percentage calculated
    ↓
Component getJobProgress() reads latest data
    ↓
View displays with animations
```

### 3. Progress Calculation
```php
// Dalam SyncBatchProgress model
$progress_percentage = ($processed_records / $total_records) * 100
```

## Testing Progress Monitoring

### Test 1: Visual Monitoring
1. Buka halaman Synchronize
2. Click "Sinkron" pada salah satu job
3. Observasi:
   - ✅ Statistics card "Sedang Berjalan" increment
   - ✅ Progress bar muncul dengan animasi
   - ✅ Status badge berubah ke "Berjalan" dengan spinner
   - ✅ Text "Sedang memproses..." muncul
   - ✅ Setiap 5 detik progress bar bertambah

### Test 2: Multiple Jobs
1. Click "Sinkronisasi Semua"
2. Observasi:
   - ✅ Statistics "Sedang Berjalan" menunjukkan jumlah job aktif
   - ✅ Semua job menampilkan progress individual
   - ✅ Icon refresh di card "Sedang Berjalan" berputar
   - ✅ Jobs selesai satu per satu

### Test 3: Expand Details
1. Click icon dropdown pada job yang berjalan
2. Observasi:
   - ✅ Statistics cards menampilkan angka real-time
   - ✅ Processed records bertambah
   - ✅ Timeline menunjukkan "Dimulai" timestamp
   - ✅ Saat selesai, "Selesai" dan "Durasi" muncul

## Performance Optimization

### 1. Efficient Queries
```php
// Hanya query job terakhir per type
SyncBatchProgress::where('institusi_id', $institusi->id)
    ->where('sync_type', $syncType)
    ->latest('id')
    ->first();
```

### 2. Conditional Rendering
```blade
<!-- Hanya render animasi jika status = processing -->
@if($progress['status'] === 'processing')
    <div class="animate-shimmer"></div>
@endif
```

### 3. Livewire Polling
- Menggunakan `wire:poll.5s` lebih efisien dari JavaScript `setInterval`
- Otomatis stop polling saat user navigate away
- Hanya update component yang berubah

## Troubleshooting

### Progress tidak update
**Solusi:**
1. Pastikan queue worker berjalan: `ps aux | grep queue:work`
2. Check browser console untuk errors
3. Verify Livewire scripts loaded

### Animasi tidak smooth
**Solusi:**
1. Clear browser cache
2. Check Tailwind CSS loaded correctly
3. Verify CSS animations defined

### Statistics tidak akurat
**Solusi:**
1. Check database `sync_batch_progress` table
2. Verify `getOverallStats()` logic
3. Test dengan `php artisan tinker`

## Tips Penggunaan

### Monitoring Job yang Lama
Untuk job dengan data banyak (misal Mahasiswa):
1. Click expand untuk lihat detail
2. Monitor "Processed" counter
3. Estimasi waktu dari progress percentage

### Debugging Failed Jobs
1. Check status badge warna merah
2. Expand row untuk lihat failed records
3. Check logs: `storage/logs/laravel.log`
4. Check failed_jobs table

### Optimal Experience
- Biarkan halaman terbuka saat sync berjalan
- Jangan refresh manual (biarkan auto-refresh)
- Gunakan expand row untuk detail

---

**Version**: 1.0.0  
**Last Updated**: November 8, 2025  
**Auto-Refresh Interval**: 5 seconds
