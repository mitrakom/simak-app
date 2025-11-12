# Custom Theme System untuk Multi-Tenancy

## 📋 Overview

Sistem theme custom memungkinkan setiap institusi (tenant) untuk memiliki konfigurasi warna tema yang unik, logo, favicon, dan custom CSS.

## 🎨 Fitur Theme

### Kolom Database (Tabel `institusis`)

| Kolom | Type | Default | Deskripsi |
|-------|------|---------|-----------|
| `theme_primary_color` | string(50) | 'blue' | Warna primer (primary) |
| `theme_secondary_color` | string(50) | 'purple' | Warna sekunder (secondary) |
| `theme_accent_color` | string(50) | 'indigo' | Warna aksen (accent) |
| `logo_path` | string | NULL | Path ke file logo institusi |
| `favicon_path` | string | NULL | Path ke file favicon |
| `custom_css` | text | NULL | Custom CSS tambahan |
| `theme_mode` | enum | 'auto' | Mode tema: 'light', 'dark', 'auto' |

## 🎨 Warna yang Tersedia

```php
'slate', 'gray', 'zinc', 'neutral', 'stone',
'red', 'orange', 'amber', 'yellow', 'lime',
'green', 'emerald', 'teal', 'cyan', 'sky',
'blue', 'indigo', 'violet', 'purple', 'fuchsia',
'pink', 'rose'
```

## 💻 Penggunaan di Code

### 1. Helper Functions

#### `theme_color()`
Mendapatkan class warna theme untuk elemen tertentu:

```php
// Syntax: theme_color($type, $variant, $shade)

// Background primary dengan shade 500
theme_color('primary', 'bg', '500') // Output: 'bg-blue-500'

// Text secondary dengan shade 600
theme_color('secondary', 'text', '600') // Output: 'text-purple-600'

// Border accent dengan shade 500
theme_color('accent', 'border', '500') // Output: 'border-indigo-500'
```

#### `theme_gradient()`
Mendapatkan gradient background:

```php
// Gradient dari primary ke secondary
theme_gradient() // Output: 'bg-gradient-to-br from-blue-500 to-purple-600'

// Custom direction
theme_gradient('r') // Output: 'bg-gradient-to-r from-blue-500 to-purple-600'
```

#### `current_institusi_theme()`
Mendapatkan semua konfigurasi theme:

```php
$theme = current_institusi_theme();
// Returns:
// [
//     'primary' => 'blue',
//     'secondary' => 'purple',
//     'accent' => 'indigo',
//     'mode' => 'auto',
//     'logo' => null,
//     'favicon' => null,
//     'custom_css' => null,
// ]
```

### 2. Blade Component

Gunakan component `<x-theme-styles>` di layout:

```blade
<head>
    ...
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Theme Styles -->
    <x-theme-styles :institusi="$currentInstitusi ?? null" />
    
    @livewireStyles
</head>
```

### 3. CSS Classes Tersedia

#### Menggunakan CSS Variables
```html
<!-- Background primary -->
<div class="theme-primary-bg">...</div>

<!-- Background primary light -->
<div class="theme-primary-bg-light">...</div>

<!-- Text primary -->
<p class="theme-primary-text">...</p>

<!-- Gradient -->
<div class="theme-gradient">...</div>

<!-- Hover states -->
<button class="theme-hover-bg-primary">...</button>

<!-- Focus states -->
<input class="theme-focus-ring" />
```

#### Menggunakan Dynamic Classes
```blade
<!-- Di Blade -->
<div class="{{ theme_color('primary', 'bg', '500') }}">
    Primary Background
</div>

<button class="{{ theme_color('primary', 'text', '600') }} hover:{{ theme_color('primary', 'bg', '50') }}">
    Themed Button
</button>

<div class="{{ theme_gradient('br') }}">
    Gradient Background
</div>
```

### 4. Model Methods

```php
use App\Models\Institusi;

$institusi = Institusi::where('slug', 'uit')->first();

// Get theme config
$theme = $institusi->getThemeConfig();

// Get theme classes
$classes = $institusi->getThemeClasses();

// Get available colors
$colors = Institusi::getAvailableColors();
```

## 🔧 Konfigurasi Theme untuk Institusi

### Via Database/Seeder

```php
use App\Models\Institusi;

Institusi::create([
    'nama' => 'Universitas Indonesia Timur',
    'slug' => 'uit',
    'theme_primary_color' => 'blue',
    'theme_secondary_color' => 'indigo',
    'theme_accent_color' => 'sky',
    'theme_mode' => 'auto',
    'custom_css' => '
        .custom-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    ',
]);
```

### Via Factory (Testing)

```php
use App\Models\Institusi;

$institusi = Institusi::factory()->create([
    'slug' => 'test-uni',
    'theme_primary_color' => 'emerald',
    'theme_secondary_color' => 'teal',
]);
```

## 📝 Contoh Implementasi

### Sidebar dengan Theme

```blade
<aside class="bg-white dark:bg-gray-800">
    <!-- Logo dengan gradient theme -->
    <div class="{{ theme_gradient('br') }} rounded-xl">
        <svg class="text-white">...</svg>
    </div>
    
    <!-- Menu aktif dengan primary color -->
    <a href="#" class="{{ request()->routeIs('dashboard') 
        ? theme_color('primary', 'bg', '50') . ' ' . theme_color('primary', 'text', '600')
        : 'text-gray-700' }}">
        Dashboard
    </a>
</aside>
```

### Button dengan Theme

```blade
<button class="{{ theme_color('primary', 'bg', '600') }} 
               {{ theme_color('primary', 'text', '50') }}
               hover:{{ theme_color('primary', 'bg', '700') }}
               rounded-lg px-4 py-2">
    Themed Button
</button>
```

### Card dengan Theme Border

```blade
<div class="border-2 {{ theme_color('primary', 'border', '500') }} rounded-lg p-6">
    <h3 class="{{ theme_color('primary', 'text', '700') }}">Title</h3>
    <p class="text-gray-600">Content</p>
</div>
```

## 🧪 Testing

Run seeder untuk membuat contoh institusi dengan theme berbeda:

```bash
php artisan db:seed --class=InstitusiThemeSeeder
```

Ini akan membuat 5 institusi dengan theme yang berbeda:
- UIT: Blue & Indigo
- UNM: Emerald & Teal  
- UNHAS: Red & Orange
- ITS: Purple & Violet
- UGM: Amber & Yellow

## 🎯 Best Practices

1. **Gunakan helper functions** untuk dynamic theming
2. **Fallback ke default** jika institusi tidak ada
3. **Test dengan berbagai warna** untuk memastikan kontras yang baik
4. **Gunakan CSS variables** untuk consistency
5. **Custom CSS** hanya untuk styling yang sangat spesifik

## 📚 Resources

- Tailwind Colors: https://tailwindcss.com/docs/customizing-colors
- CSS Variables: https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties
