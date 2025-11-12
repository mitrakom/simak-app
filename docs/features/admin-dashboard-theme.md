# Admin Dashboard Theme & Layout

Dokumentasi lengkap tentang theme dan layout admin dashboard SIMAK App.

## Overview

Admin dashboard menggunakan **modern sidebar layout** dengan navbar sticky di bagian atas. Layout ini fully responsive dan mendukung dark mode.

## Struktur Layout

### 1. Main Layout (`components/layouts/admin.blade.php`)

Layout utama yang membungkus semua halaman admin:

```blade
<x-layouts.admin>
    <x-slot name="header">Dashboard</x-slot>
    
    <!-- Content here -->
</x-layouts.admin>
```

**Features:**
- ✅ Dark mode support (persistent via localStorage)
- ✅ Responsive design (mobile-first)
- ✅ Institusi-specific theming via `<x-theme-styles>`
- ✅ Livewire integration

### 2. Sidebar (`components/admin/sidebar.blade.php`)

Sidebar navigasi dengan menu collapsible:

**Features:**
- ✅ Fixed position di sebelah kiri
- ✅ Auto-hide pada mobile (toggle dengan hamburger menu)
- ✅ Active state highlighting
- ✅ Collapsible submenu dengan Alpine.js
- ✅ User profile di bagian bawah (dinamis dari Auth)
- ✅ Institusi logo dan nama

**Menu Structure:**

```
Dashboard
├── Main Menu
│   ├── Dashboard
│   ├── Users
│   ├── Analytics
│   └── Synchronize
│
├── Management
│   ├── Analisis Akademik (collapsible)
│   │   ├── Peta Perjalanan
│   │   ├── Sebaran IPS
│   │   └── Monitoring Bimbingan
│   │
│   ├── Laporan Strategis (collapsible)
│   │   ├── Kesiapan Akreditasi
│   │   └── Pelaporan Prodi
│   │
│   └── Data Master (collapsible)
│       ├── Prodi
│       ├── Mahasiswa
│       └── Dosen
│
└── System
    └── Settings
```

### 3. Navbar (`components/admin/navbar.blade.php`)

Top navigation bar dengan utilities:

**Features:**
- ✅ Sticky position (selalu terlihat saat scroll)
- ✅ Mobile menu toggle button
- ✅ Search bar (desktop only)
- ✅ Dark mode toggle
- ✅ Notifications dropdown
- ✅ User menu dropdown (dinamis dari Auth)
- ✅ Logout functionality

## Color Scheme

### Light Mode
- Background: `bg-gray-100`
- Card: `bg-white`
- Text: `text-gray-900`
- Border: `border-gray-200`
- Accent: `bg-blue-600` (primary action)

### Dark Mode
- Background: `bg-gray-900`
- Card: `bg-gray-800`
- Text: `text-white`
- Border: `border-gray-700`
- Accent: `bg-blue-600` (primary action, tetap sama)

### Brand Colors
- Primary: Blue (`blue-600`)
- Success: Green (`green-600`)
- Danger: Red (`red-600`)
- Warning: Yellow (`yellow-600`)
- Info: Cyan (`cyan-600`)

## Responsive Breakpoints

Menggunakan Tailwind CSS breakpoints:

- **Mobile**: < 1024px (sidebar hidden, toggle required)
- **Desktop**: >= 1024px (lg:, sidebar always visible)

```blade
<!-- Contoh responsive class -->
<div class="p-4 lg:p-6">         <!-- Padding lebih besar di desktop -->
<div class="lg:pl-64">            <!-- Padding left untuk sidebar di desktop -->
<div class="hidden lg:block">     <!-- Hidden di mobile -->
<div class="lg:hidden">           <!-- Hidden di desktop -->
```

## Dark Mode Implementation

Dark mode menggunakan Alpine.js dan localStorage:

```html
<html x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true' || 
              (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches) 
}" 
x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" 
:class="{ 'dark': darkMode }">
```

**Toggle button di navbar:**
```html
<button @click="darkMode = !darkMode">
    <!-- Sun icon (show in dark mode) -->
    <svg x-show="darkMode">...</svg>
    <!-- Moon icon (show in light mode) -->
    <svg x-show="!darkMode">...</svg>
</button>
```

## Active State Highlighting

Menu yang aktif mendapat highlight otomatis:

```blade
<a href="..." 
   class="{{ request()->routeIs('admin.dashboard') 
       ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' 
       : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
    Dashboard
</a>
```

## Collapsible Submenu

Submenu menggunakan Alpine.js `x-collapse` directive:

```blade
<div x-data="{ analysisOpen: false }">
    <button @click="analysisOpen = !analysisOpen">
        Analisis Akademik
        <svg :class="{ 'rotate-180': analysisOpen }">...</svg>
    </button>
    
    <div x-show="analysisOpen" x-collapse>
        <!-- Submenu items -->
    </div>
</div>
```

## Mobile Sidebar Toggle

Sidebar di mobile menggunakan event dispatcher:

```blade
<!-- Navbar: Mobile Menu Button -->
<button @click="$dispatch('toggle-sidebar')">
    <svg>...</svg>
</button>

<!-- Sidebar: Listen to event -->
<aside 
    x-data="{ sidebarOpen: false }" 
    @toggle-sidebar.window="sidebarOpen = !sidebarOpen"
    :class="{ '-translate-x-full': !sidebarOpen }">
    ...
</aside>

<!-- Overlay: Close on click -->
<div 
    x-data="{ open: false }"
    @toggle-sidebar.window="open = !open"
    x-show="open" 
    @click="open = false"
    class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden">
</div>
```

## User Profile Integration

Sidebar dan navbar menggunakan data user yang dinamis:

```blade
@php
    $user = Auth::user();
    $initials = $user ? strtoupper(substr($user->name, 0, 2)) : 'AD';
@endphp

<!-- Avatar with initials -->
<div class="size-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full">
    {{ $initials }}
</div>

<!-- User info -->
<p>{{ $user->name ?? 'Admin User' }}</p>
<p>{{ $user->email ?? 'admin@example.com' }}</p>
```

## Institusi Context

Semua link menggunakan institusi slug dari route:

```blade
@props(['currentInstitusi' => null])

<a href="{{ route('admin.dashboard', ['institusi' => $currentInstitusi->slug ?? request()->route('institusi')->slug]) }}">
    Dashboard
</a>
```

## Consistency Guidelines

### ✅ DO's:
- Selalu gunakan komponen yang sudah ada (`<x-card>`, `<x-button>`, dll)
- Gunakan color scheme yang konsisten (blue untuk primary, green untuk success, dll)
- Pastikan dark mode support di setiap komponen baru
- Tambahkan active state pada menu navigasi
- Gunakan spacing yang konsisten (`gap-3`, `px-3 py-2`, dll)

### ❌ DON'Ts:
- Jangan hardcode user data (gunakan `Auth::user()`)
- Jangan membuat color scheme baru tanpa konsultasi
- Jangan skip responsive design (mobile-first)
- Jangan lupa dark mode classes (`dark:...`)
- Jangan membuat komponen baru kalau sudah ada yang serupa

## Customization

### Mengubah Warna Brand

Edit di `resources/views/components/theme-styles.blade.php` atau Tailwind config:

```css
:root {
    --color-primary: oklch(0.61 0.20 231); /* Blue-600 */
}
```

### Menambah Menu Baru

1. Tambah route di `routes/web.php`
2. Tambah menu item di `sidebar.blade.php`
3. Pastikan active state checking benar
4. Test responsive & dark mode

```blade
<a href="{{ route('admin.new-feature', ['institusi' => $currentInstitusi->slug]) }}" 
   class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors 
          {{ request()->routeIs('admin.new-feature*') 
              ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
    <svg class="size-5">...</svg>
    New Feature
</a>
```

## Troubleshooting

### Sidebar tidak toggle di mobile
- Pastikan Alpine.js loaded
- Check event dispatcher di navbar button
- Verify z-index overlay dan sidebar

### Dark mode tidak persist
- Check localStorage access di browser
- Verify Alpine.js `x-init` running
- Inspect `darkMode` reactive data

### Active state tidak highlight
- Check route name matching di `request()->routeIs()`
- Pastikan route name konsisten
- Verify conditional classes benar

### User data tidak muncul
- Pastikan user sudah login (`Auth::check()`)
- Verify `Auth::user()` tidak null
- Check middleware `auth` di route

## Performance Tips

1. **Lazy load icons**: Gunakan SVG inline untuk ikon yang sering digunakan
2. **Minimize Alpine.js reactivity**: Hanya gunakan `x-data` saat perlu interactivity
3. **Optimize transitions**: Gunakan `transition-colors` daripada `transition-all`
4. **Cache routes**: Jalankan `php artisan route:cache` di production
