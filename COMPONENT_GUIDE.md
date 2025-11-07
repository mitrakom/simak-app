# Admin Dashboard - Panduan Penggunaan Komponen

Panduan lengkap penggunaan komponen Tailwind CSS yang telah dibuat untuk Admin Dashboard SIMAK App.

## Daftar Isi
1. [Layout](#layout)
2. [Komponen Dasar](#komponen-dasar)
3. [Komponen Form](#komponen-form)
4. [Contoh Penggunaan](#contoh-penggunaan)

---

## Layout

### Admin Layout
Layout utama untuk halaman admin dengan sidebar dan navbar.

```blade
<x-layouts.admin>
    <x-slot name="header">Judul Halaman</x-slot>
    
    <!-- Konten halaman Anda -->
</x-layouts.admin>
```

**Props:**
- `header` (slot) - Judul halaman yang akan ditampilkan di bagian atas

---

## Komponen Dasar

### 1. Card Component

Komponen card untuk menampilkan konten dalam kotak yang rapi.

```blade
<!-- Card Sederhana -->
<x-card>
    Konten card Anda
</x-card>

<!-- Card dengan Title -->
<x-card title="Judul Card">
    Konten card Anda
</x-card>

<!-- Card dengan Footer -->
<x-card title="Judul Card">
    Konten card Anda
    
    <x-slot name="footer">
        <x-button>Action Button</x-button>
    </x-slot>
</x-card>

<!-- Card Tanpa Padding -->
<x-card :padding="false">
    <!-- Berguna untuk table atau content full-width -->
</x-card>

<!-- Card Tanpa Shadow -->
<x-card :shadow="false">
    Konten tanpa shadow
</x-card>
```

**Props:**
- `title` - Judul card (opsional)
- `footer` (slot) - Footer card (opsional)
- `padding` - Tambahkan padding (default: true)
- `shadow` - Tambahkan shadow (default: true)

---

### 2. Button Component

Tombol dengan berbagai variant dan ukuran, mirip Bootstrap.

```blade
<!-- Variants -->
<x-button variant="primary">Primary</x-button>
<x-button variant="secondary">Secondary</x-button>
<x-button variant="success">Success</x-button>
<x-button variant="danger">Danger</x-button>
<x-button variant="warning">Warning</x-button>
<x-button variant="info">Info</x-button>

<!-- Sizes -->
<x-button size="sm">Small</x-button>
<x-button size="md">Medium</x-button>
<x-button size="lg">Large</x-button>

<!-- Outline Variant -->
<x-button variant="primary" :outline="true">Outline Primary</x-button>

<!-- As Link -->
<x-button href="/dashboard" variant="primary">Go to Dashboard</x-button>

<!-- Dengan Icon -->
<x-button variant="primary">
    <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
    </svg>
    Add New
</x-button>

<!-- Disabled -->
<x-button disabled>Disabled Button</x-button>
```

**Props:**
- `variant` - primary, secondary, success, danger, warning, info (default: primary)
- `size` - sm, md, lg (default: md)
- `outline` - Mode outline (default: false)
- `type` - button, submit, reset (default: button)
- `href` - Jika diset, akan render sebagai link

---

### 3. Badge Component

Label kecil untuk status atau kategori.

```blade
<!-- Variants -->
<x-badge variant="primary">Primary</x-badge>
<x-badge variant="secondary">Secondary</x-badge>
<x-badge variant="success">Success</x-badge>
<x-badge variant="danger">Danger</x-badge>
<x-badge variant="warning">Warning</x-badge>
<x-badge variant="info">Info</x-badge>

<!-- Sizes -->
<x-badge size="sm">Small</x-badge>
<x-badge size="md">Medium</x-badge>
<x-badge size="lg">Large</x-badge>

<!-- Dengan Dot Indicator -->
<x-badge variant="success" :dot="true">Active</x-badge>
<x-badge variant="danger" :dot="true">Inactive</x-badge>
```

**Props:**
- `variant` - primary, secondary, success, danger, warning, info (default: primary)
- `size` - sm, md, lg (default: md)
- `dot` - Tampilkan dot indicator (default: false)

---

### 4. Alert Component

Pesan notifikasi atau informasi penting.

```blade
<!-- Variants -->
<x-alert variant="success">
    Operasi berhasil dilakukan!
</x-alert>

<x-alert variant="danger">
    Terjadi kesalahan! Silakan coba lagi.
</x-alert>

<x-alert variant="warning">
    Peringatan: Data akan dihapus permanen.
</x-alert>

<x-alert variant="info">
    Informasi: Sistem akan maintenance pada malam hari.
</x-alert>

<!-- Dismissible Alert -->
<x-alert variant="success" :dismissible="true">
    Alert ini bisa ditutup dengan tombol X
</x-alert>

<!-- Alert Tanpa Icon -->
<x-alert variant="info" :icon="false">
    Alert tanpa icon
</x-alert>

<!-- Alert dengan Konten HTML -->
<x-alert variant="info">
    <strong class="font-semibold">Judul Alert</strong>
    <p class="mt-1">Deskripsi lebih detail tentang alert ini.</p>
</x-alert>
```

**Props:**
- `variant` - success, danger, warning, info (default: info)
- `dismissible` - Bisa ditutup (default: false)
- `icon` - Tampilkan icon (default: true)

---

### 5. Stat Card Component

Card khusus untuk menampilkan statistik.

```blade
<x-stat-card
    title="Total Users"
    value="1,250"
    change="+12.5% from last month"
    change-type="positive"
    color="blue"
>
    <x-slot name="icon">
        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- SVG icon -->
        </svg>
    </x-slot>
</x-stat-card>
```

**Props:**
- `title` - Judul statistik
- `value` - Nilai statistik
- `icon` (slot) - Icon SVG
- `change` - Teks perubahan (opsional)
- `changeType` - positive, negative, neutral (default: neutral)
- `color` - blue, green, red, yellow, purple, pink (default: blue)

---

## Komponen Form

### 1. Input Component

Input field dengan label dan error message.

```blade
<!-- Input Sederhana -->
<x-input 
    label="Email" 
    type="email" 
    name="email"
    placeholder="Enter your email"
/>

<!-- Input dengan Required Indicator -->
<x-input 
    label="Password" 
    type="password" 
    :required="true"
/>

<!-- Input dengan Error Message -->
<x-input 
    label="Username" 
    error="Username sudah digunakan"
/>

<!-- Input dengan Hint -->
<x-input 
    label="Website" 
    hint="Masukkan URL lengkap dengan https://"
/>

<!-- Input dengan Wire Model (Livewire) -->
<x-input 
    label="Search" 
    wire:model.live.debounce.300ms="search"
    placeholder="Search..."
/>
```

**Props:**
- `label` - Label input (opsional)
- `error` - Pesan error (opsional)
- `hint` - Hint text (opsional)
- `type` - Tipe input (default: text)
- `required` - Tampilkan tanda * (default: false)

---

### 2. Textarea Component

Textarea dengan label dan error message.

```blade
<!-- Textarea Sederhana -->
<x-textarea 
    label="Description"
    name="description"
/>

<!-- Textarea dengan Rows Custom -->
<x-textarea 
    label="Message"
    :rows="6"
/>

<!-- Textarea dengan Error -->
<x-textarea 
    label="Content"
    error="Content minimal 100 karakter"
/>

<!-- Dengan Wire Model -->
<x-textarea 
    label="Notes"
    wire:model="notes"
/>
```

**Props:**
- `label` - Label textarea (opsional)
- `error` - Pesan error (opsional)
- `hint` - Hint text (opsional)
- `rows` - Jumlah baris (default: 4)
- `required` - Tampilkan tanda * (default: false)

---

### 3. Select Component

Dropdown select dengan label dan error message.

```blade
<!-- Select Sederhana -->
<x-select label="Role" name="role">
    <option value="">Pilih Role</option>
    <option value="admin">Admin</option>
    <option value="user">User</option>
</x-select>

<!-- Select dengan Error -->
<x-select 
    label="Category" 
    error="Kategori harus dipilih"
>
    <option value="">-- Pilih Kategori --</option>
    <option value="1">Category 1</option>
    <option value="2">Category 2</option>
</x-select>

<!-- Dengan Wire Model -->
<x-select 
    label="Status"
    wire:model="status"
>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
</x-select>
```

**Props:**
- `label` - Label select (opsional)
- `error` - Pesan error (opsional)
- `hint` - Hint text (opsional)
- `required` - Tampilkan tanda * (default: false)

---

## Contoh Penggunaan

### Contoh 1: Halaman dengan Grid Layout

```blade
<x-layouts.admin>
    <x-slot name="header">Dashboard</x-slot>

    <!-- Grid 4 Kolom untuk Statistik -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-stat-card
            title="Total Users"
            value="1,250"
            change="+12.5%"
            change-type="positive"
            color="blue"
        />
        <!-- Stat cards lainnya -->
    </div>

    <!-- Grid 2 Kolom untuk Content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Recent Activity">
            <!-- Content -->
        </x-card>
        
        <x-card title="Top Products">
            <!-- Content -->
        </x-card>
    </div>
</x-layouts.admin>
```

### Contoh 2: Form dengan Validasi

```blade
<x-layouts.admin>
    <x-slot name="header">Create User</x-slot>

    <x-card title="User Information">
        <form wire:submit="save" class="space-y-4">
            <x-input 
                label="Full Name" 
                wire:model="name"
                :required="true"
                :error="$errors->first('name')"
            />
            
            <x-input 
                label="Email" 
                type="email"
                wire:model="email"
                :required="true"
                :error="$errors->first('email')"
            />
            
            <x-select 
                label="Role"
                wire:model="role"
                :required="true"
            >
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </x-select>
            
            <x-textarea 
                label="Bio"
                wire:model="bio"
                hint="Tell us about yourself"
            />
            
            <div class="flex gap-2">
                <x-button type="submit" variant="primary">
                    Save User
                </x-button>
                <x-button type="button" variant="secondary" href="/admin/users">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
</x-layouts.admin>
```

### Contoh 3: Table dengan Actions

```blade
<x-card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4">{{ $item->name }}</td>
                        <td class="px-6 py-4">
                            <x-badge :variant="$item->is_active ? 'success' : 'secondary'">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button variant="secondary" size="sm" :outline="true">
                                    Edit
                                </x-button>
                                <x-button variant="danger" size="sm" :outline="true">
                                    Delete
                                </x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
```

---

## Tips Penggunaan Tailwind CSS

### 1. Spacing (Jarak)
Gunakan gap untuk spacing antar elemen:
```blade
<!-- Spacing vertikal -->
<div class="space-y-4">
    <x-card>Card 1</x-card>
    <x-card>Card 2</x-card>
</div>

<!-- Spacing horizontal -->
<div class="flex gap-2">
    <x-button>Button 1</x-button>
    <x-button>Button 2</x-button>
</div>

<!-- Grid dengan gap -->
<div class="grid grid-cols-3 gap-6">
    <!-- Items -->
</div>
```

### 2. Responsive Design
```blade
<!-- 1 kolom mobile, 2 tablet, 4 desktop -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Items -->
</div>

<!-- Hide on mobile, show on desktop -->
<div class="hidden lg:block">
    Desktop only content
</div>

<!-- Show on mobile, hide on desktop -->
<div class="block lg:hidden">
    Mobile only content
</div>
```

### 3. Dark Mode
Semua komponen sudah support dark mode dengan prefix `dark:`:
```blade
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    Content otomatis berubah sesuai tema
</div>
```

### 4. Flexbox
```blade
<!-- Center items -->
<div class="flex items-center justify-center">
    Centered content
</div>

<!-- Space between -->
<div class="flex items-center justify-between">
    <span>Left</span>
    <span>Right</span>
</div>

<!-- Vertical stack -->
<div class="flex flex-col gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

---

## Kesimpulan

Semua komponen yang telah dibuat sangat fleksibel dan reusable. Anda dapat:
- Menggabungkan berbagai komponen untuk membuat halaman yang kompleks
- Menggunakan props untuk customisasi tampilan
- Menambahkan class Tailwind tambahan jika diperlukan
- Semua komponen sudah responsive dan support dark mode

Untuk pertanyaan lebih lanjut, silakan lihat dokumentasi Tailwind CSS di https://tailwindcss.com/docs
