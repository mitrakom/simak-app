# Test: Multi-Tenant Login System

**Tanggal Test**: 08/11/2025  
**Tester**: Tim Development  
**Build/Version**: v1.0.0-dev

## Tujuan Test
Memastikan sistem login multi-tenant berfungsi dengan baik untuk setiap institusi dan mencegah cross-tenant access.

## Pre-kondisi
- Aplikasi berjalan di `http://localhost:8900`
- Database sudah di-seed dengan data institusi (uit, unhas)
- User test sudah dibuat:
  - Email: `admin@uit.ac.id`, Password: `password123`, Institusi: UIT
  - Email: `admin@unhas.ac.id`, Password: `password123`, Institusi: UNHAS

## Langkah Test

### Test Case 1: Login dengan Kredensial Benar
1. Akses `http://localhost:8900/uit/auth/login`
2. Input email: `admin@uit.ac.id`
3. Input password: `password123`
4. Klik tombol "Login"

**Expected Result**: 
- User berhasil login
- Redirect ke `/uit/admin/dashboard`
- Muncul pesan sukses "Login berhasil! Selamat datang, [Nama User]"

**Actual Result**: ✅ PASS - User berhasil login dan redirect ke dashboard

**Status**: ✅ PASS

### Test Case 2: Login dengan Password Salah
1. Akses `http://localhost:8900/uit/auth/login`
2. Input email: `admin@uit.ac.id`
3. Input password: `wrongpassword`
4. Klik tombol "Login"

**Expected Result**: 
- Login gagal
- Tetap di halaman login
- Muncul error message "These credentials do not match our records."

**Actual Result**: ✅ PASS - Error message muncul dengan benar

**Status**: ✅ PASS

### Test Case 3: Login dengan Email Tidak Terdaftar
1. Akses `http://localhost:8900/uit/auth/login`
2. Input email: `notexist@uit.ac.id`
3. Input password: `password123`
4. Klik tombol "Login"

**Expected Result**: 
- Login gagal
- Muncul error message

**Actual Result**: ✅ PASS - Error message muncul

**Status**: ✅ PASS

### Test Case 4: Cross-Tenant Access Prevention
1. Login sebagai `admin@uit.ac.id` di `/uit/auth/login`
2. Setelah login berhasil, manual akses `/unhas/admin/dashboard`

**Expected Result**: 
- User otomatis di-logout
- Redirect ke `/unhas/auth/login`
- Muncul error "User tidak terdaftar pada institusi ini"

**Actual Result**: ✅ PASS - Cross-tenant access dicegah dengan benar

**Status**: ✅ PASS

### Test Case 5: Session Persistence
1. Login sebagai `admin@uit.ac.id`
2. Redirect ke dashboard
3. Refresh halaman dashboard
4. Navigate ke halaman lain (e.g., `/uit/admin/users`)

**Expected Result**: 
- Session tetap aktif
- User tetap authenticated
- Tidak perlu login ulang

**Actual Result**: ✅ PASS - Session bertahan dengan baik

**Status**: ✅ PASS

### Test Case 6: Logout Functionality
1. Login sebagai `admin@uit.ac.id`
2. Klik tombol "Logout" di navbar
3. Coba akses `/uit/admin/dashboard` lagi

**Expected Result**: 
- User berhasil logout
- Session dihapus
- Redirect ke `/uit/auth/login`
- Akses ke dashboard redirect ke login

**Actual Result**: ✅ PASS - Logout berfungsi dengan benar

**Status**: ✅ PASS

## Environment
- OS: Ubuntu 22.04
- Browser: Google Chrome 119
- Database: MySQL 8.0
- PHP: 8.3.18
- Laravel: v12

## Catatan
- Middleware `EnsureUserBelongsToInstitusi` bekerja dengan baik untuk mencegah cross-tenant access
- Session menggunakan database driver dan berfungsi stabil
- Perlu ditambahkan rate limiting untuk mencegah brute force attack
- Consider menambahkan 2FA untuk keamanan tambahan

## Screenshot
(Screenshots dapat ditambahkan di folder `/docs/screenshots/`)

## Follow-up Actions
- [ ] Implementasi rate limiting pada login
- [ ] Tambah logging untuk failed login attempts
- [ ] Consider implementasi 2FA
- [ ] Buat dokumentasi untuk recovery password
