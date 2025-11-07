# SIMAK App - Admin Dashboard

Template Admin Dashboard profesional yang dibuat dengan Laravel 12, Livewire 3, dan Tailwind CSS 4.

## 🎯 Fitur Utama

### Komponen Reusable
Komponen yang mirip dengan Bootstrap, mudah digunakan dan fleksibel:
- **Card** - Container untuk konten
- **Button** - Tombol dengan berbagai variant (primary, secondary, success, danger, warning, info)
- **Badge** - Label untuk status atau kategori
- **Alert** - Pesan notifikasi
- **Input, Textarea, Select** - Form components dengan validasi
- **Stat Card** - Card khusus untuk statistik

### Layout Admin
- **Sidebar** - Navigasi samping yang responsive dengan mobile menu
- **Navbar** - Header dengan search, notifications, dan user menu
- **Dark Mode** - Support otomatis mengikuti system preference

### Halaman
- **Dashboard** - Halaman utama dengan statistik dan recent activity
- **Users Management** - Contoh halaman CRUD dengan table dan filter

## 🚀 Cara Menjalankan

### 1. Start Development Server

```bash
# Terminal 1 - Laravel Server
php artisan serve

# Terminal 2 - Vite Dev Server (untuk hot reload)
npm run dev
```

### 2. Akses Aplikasi

- **Homepage**: http://127.0.0.1:8000
- **Dashboard**: http://127.0.0.1:8000/admin
- **Users**: http://127.0.0.1:8000/admin/users

### 3. Build untuk Production

```bash
# Build assets
npm run build

# Server akan otomatis menggunakan built assets
php artisan serve
```

## 📚 Dokumentasi

Lihat file `COMPONENT_GUIDE.md` untuk dokumentasi lengkap tentang cara menggunakan semua komponen yang tersedia.

### Contoh Cepat

#### Membuat Halaman Baru dengan Volt

```bash
# Buat component Volt baru
php artisan make:volt admin/products --pest
```

```blade
@volt
<?php
use function Livewire\Volt\{state};

state(['products' => []]);
?>

<x-layouts.admin>
    <x-slot name="header">Products</x-slot>

    <div class="space-y-6">
        <x-card title="Product List">
            <!-- Content here -->
        </x-card>
    </div>
</x-layouts.admin>
@endvolt
```

#### Menambahkan Route

```php
// routes/web.php
Route::prefix('admin')->name('admin.')->group(function () {
    Volt::route('/', 'admin.dashboard')->name('dashboard');
    Volt::route('/users', 'admin.users')->name('users');
    Volt::route('/products', 'admin.products')->name('products'); // NEW
});
```

## 🎨 Tailwind CSS Tips

### Grid Layout
```blade
<!-- 1 kolom mobile, 2 tablet, 4 desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <x-card>Card 1</x-card>
    <x-card>Card 2</x-card>
    <x-card>Card 3</x-card>
    <x-card>Card 4</x-card>
</div>
```

### Spacing
```blade
<!-- Vertical spacing -->
<div class="space-y-6">
    <x-card>Card 1</x-card>
    <x-card>Card 2</x-card>
</div>

<!-- Horizontal spacing dengan flex -->
<div class="flex gap-4">
    <x-button>Button 1</x-button>
    <x-button>Button 2</x-button>
</div>
```

### Responsive
```blade
<!-- Hide pada mobile, show pada desktop -->
<div class="hidden lg:block">Desktop only</div>

<!-- Show pada mobile, hide pada desktop -->
<div class="block lg:hidden">Mobile only</div>
```

## 🔧 Struktur File

```
resources/
├── views/
│   ├── components/
│   │   ├── admin/
│   │   │   ├── navbar.blade.php       # Top navbar
│   │   │   └── sidebar.blade.php      # Side navigation
│   │   ├── alert.blade.php            # Alert component
│   │   ├── badge.blade.php            # Badge component
│   │   ├── button.blade.php           # Button component
│   │   ├── card.blade.php             # Card component
│   │   ├── input.blade.php            # Input component
│   │   ├── select.blade.php           # Select component
│   │   ├── stat-card.blade.php        # Statistics card
│   │   └── textarea.blade.php         # Textarea component
│   ├── layouts/
│   │   └── admin.blade.php            # Main admin layout
│   ├── livewire/
│   │   └── admin/
│   │       ├── dashboard.blade.php    # Dashboard page
│   │       └── users.blade.php        # Users page
│   └── welcome.blade.php              # Landing page
├── css/
│   └── app.css                        # Tailwind CSS
└── js/
    └── app.js                         # JavaScript entry point
```

## 📖 Perbandingan dengan Bootstrap

Jika Anda terbiasa dengan Bootstrap, berikut perbandingannya:

| Bootstrap | Tailwind (Project ini) |
|-----------|------------------------|
| `<button class="btn btn-primary">` | `<x-button variant="primary">` |
| `<div class="card">` | `<x-card>` |
| `<span class="badge bg-success">` | `<x-badge variant="success">` |
| `<div class="alert alert-info">` | `<x-alert variant="info">` |
| `<div class="row"><div class="col-md-6">` | `<div class="grid grid-cols-1 md:grid-cols-2">` |
| `<input class="form-control">` | `<x-input>` |

## 🎓 Learning Resources

### Tailwind CSS
- **Dokumentasi**: https://tailwindcss.com/docs
- **Playground**: https://play.tailwindcss.com

### Laravel Livewire
- **Dokumentasi**: https://livewire.laravel.com
- **Volt**: https://livewire.laravel.com/docs/volt

### Laravel
- **Dokumentasi**: https://laravel.com/docs

## 💡 Tips Pengembangan

### 1. Hot Reload
Gunakan `npm run dev` agar perubahan CSS/JS langsung ter-reload tanpa refresh manual.

### 2. Dark Mode Testing
Gunakan DevTools browser untuk toggle dark mode:
- Chrome/Edge: F12 > ... > More tools > Rendering > Emulate CSS media feature prefers-color-scheme
- Firefox: F12 > ... > Settings > Switch dark/light theme

### 3. Responsive Testing
Gunakan DevTools untuk test di berbagai ukuran layar:
- Mobile: 375px
- Tablet: 768px
- Desktop: 1024px, 1440px

### 4. Component Customization
Semua component bisa di-customize dengan menambahkan class Tailwind:

```blade
<x-button variant="primary" class="w-full">
    Full width button
</x-button>

<x-card class="border-2 border-blue-500">
    Card dengan border custom
</x-card>
```

## 🐛 Troubleshooting

### CSS tidak muncul?
```bash
# Build ulang assets
npm run build
# atau jalankan dev server
npm run dev
```

### Component tidak ditemukan?
Pastikan nama component sesuai dengan struktur folder di `resources/views/components/`.

### Livewire error?
```bash
# Clear cache
php artisan optimize:clear
```

## 📝 Next Steps

1. **Tambahkan Authentication**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install
   ```

2. **Buat CRUD untuk model lain**
   - Copy struktur dari `users.blade.php`
   - Sesuaikan dengan model Anda

3. **Customize Theme**
   - Edit `resources/css/app.css`
   - Tambahkan custom colors di `@theme`

4. **Deploy ke Production**
   - Run `npm run build`
   - Setup environment production
   - Configure web server

## 🤝 Kontribusi

Silakan customize dan kembangkan sesuai kebutuhan project Anda!

---

**Dibuat dengan ❤️ menggunakan Laravel 12, Livewire 3, dan Tailwind CSS 4**
