# Landing Page - Multi-Tenancy Documentation

## Overview
Landing page SIMAK dirancang dengan konsep multi-tenancy dimana setiap institusi memiliki landing page unik berdasarkan slug URL mereka.

## URL Structure
```
http://simak.id/{slug}
```

**Contoh:**
- `http://localhost:8900/uit` - Landing page untuk Universitas Indonesia Timur
- `http://localhost:8900/unhas` - Landing page untuk Universitas Hasanuddin

## Files Created/Modified

### 1. Routes (`routes/web.php`)
```php
Route::get('/{institusi:slug}', [LandingPageController::class, 'show'])->name('landing');
```
- Menggunakan Route Model Binding untuk auto-resolve `Institusi` berdasarkan slug
- Jika slug tidak ditemukan, otomatis return 404

### 2. Layout Landing (`resources/views/components/layouts/landing.blade.php`)
- Layout khusus untuk landing page (berbeda dengan admin layout)
- Include navbar dengan dark mode toggle
- Include footer dengan info institusi
- Responsive dan support dark mode

### 3. Landing Page View (`resources/views/landing.blade.php`)
Struktur halaman:

#### Hero Section
- Badge dengan nama institusi & live indicator
- Main heading dengan gradient text
- Subtitle deskriptif
- CTA buttons (Mulai Sekarang & Pelajari Lebih Lanjut)
- Stats cards (Real-time, 100% Otomatis, Akurat)

#### Features Section
3 fitur unggulan dengan icon dan deskripsi:
1. **Pantau Kinerja Prodi** - Monitor kesehatan akademik real-time
2. **Analisis Mahasiswa Mendalam** - Peta perjalanan mahasiswa & deteksi drop out
3. **Percepat Akreditasi & IKU** - Otomasi pelaporan IKU & borang akreditasi

#### Benefits Section
- Visual dashboard preview dengan mock data
- List 4 manfaat utama dengan checkmark icons
- Floating visual elements untuk estetika

#### CTA Section
- Gradient background (blue to purple)
- Call-to-action untuk akses dashboard
- Link kontak email

### 4. CSS Enhancements (`resources/css/app.css`)
- Grid pattern background untuk hero section
- Support dark mode untuk grid pattern

### 5. Controller (`app/Http/Controllers/LandingPageController.php`)
- Sudah ada, menggunakan Route Model Binding
- Auto-inject `$institusi` ke view

## Components Used
Landing page menggunakan komponen yang sudah ada:
- `<x-layouts.landing>` - Layout wrapper
- `<x-button>` - CTA buttons dengan variant & size
- `<x-card>` - Feature cards
- `<x-badge>` - Status badges

## Design Principles
1. **Clean & Professional** - Minimalis dengan white space yang cukup
2. **Modern Gradient** - Menggunakan gradient blue-purple untuk brand identity
3. **Dark Mode Ready** - Full support dark mode dengan Alpine.js
4. **Responsive** - Mobile-first approach dengan Tailwind breakpoints
5. **Accessible** - Semantic HTML & ARIA labels

## Testing

### Create Sample Data
```bash
docker compose exec app php artisan tinker --execute="
App\Models\Institusi::updateOrCreate(
    ['slug' => 'uit'],
    ['nama' => 'Universitas Indonesia Timur']
);
"
```

### Access Landing Page
1. Pastikan server berjalan: `docker compose up -d`
2. Akses: `http://localhost:8900/uit`
3. Verifikasi:
   - Nama institusi muncul di navbar & badge
   - Semua section ter-render dengan baik
   - Dark mode toggle berfungsi
   - CTA buttons mengarah ke dashboard
   - 404 page muncul jika slug tidak ada

## Content Strategy
Konten landing page disesuaikan dengan ringkasan aplikasi:

**Headline:** "Transformasi Data PDDikti Menjadi Keputusan Strategis"

**Value Proposition:**
- Platform dasbor analitik canggih
- Untuk Pimpinan & Ketua Program Studi
- Ubah data kompleks jadi laporan visual interaktif

**Key Features:**
1. Monitor kinerja prodi real-time
2. Analisis mahasiswa mendalam (IPK, masa studi, deteksi drop out)
3. Otomasi pelaporan IKU & akreditasi

**Benefits:**
- Pengambilan keputusan cepat berbasis data
- Hemat waktu & tenaga (80% reduksi kerja manual)
- Integrasi seamless dengan PDDikti Feeder
- User-friendly interface tanpa training khusus

## Future Enhancements
- [ ] Tambah section testimonial dari institusi
- [ ] Galeri screenshot dashboard
- [ ] Video demo produk
- [ ] FAQ section
- [ ] Form kontak langsung
- [ ] Custom branding per institusi (logo, warna)
- [ ] Analitik pengunjung landing page
