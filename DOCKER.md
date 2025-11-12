# Docker Configuration - SIMAK Laravel

## 📋 Konfigurasi Terpusat via .env

Konfigurasi Docker menggunakan environment variables dari file `.env` untuk menghindari konflik dengan proyek lain.

### Variabel Docker di `.env`

```env
# Docker Configuration
DOCKER_APP_PORT=9100           # Port untuk PHP-FPM (default: 9100)
DOCKER_WEB_PORT=8910           # Port untuk Nginx HTTP (default: 8910)
DOCKER_CONTAINER_PREFIX=simak_laravel  # Prefix nama container
```

## 🚀 Quick Start

### 1. Build & Start Services
```bash
docker-compose up -d --build
```

### 2. Stop Services
```bash
docker-compose down
```

### 3. Akses Aplikasi
```
http://localhost:8910
```

## 🔧 Mengubah Port (Jika Konflik)

Edit file `.env` dan ubah nilai berikut:

```env
# Contoh: Jika port 8910 atau 9100 sudah digunakan
DOCKER_WEB_PORT=8920    # Ubah ke port lain
DOCKER_APP_PORT=9110    # Ubah ke port lain
```

Kemudian rebuild:
```bash
docker-compose down
docker-compose up -d --build
```

## 📦 Services

### 1. **app** (PHP-FPM)
- Container: `${DOCKER_CONTAINER_PREFIX}_app` (default: `simak_laravel_app`)
- Port: `${DOCKER_APP_PORT}` (default: `9100`)
- Resource: 1 Core CPU, 1GB RAM

### 2. **web** (Nginx)
- Container: `${DOCKER_CONTAINER_PREFIX}_web` (default: `simak_laravel_web`)
- Port: `${DOCKER_WEB_PORT}` (default: `8910`)

### 3. **node** (Frontend)
- Container: `${DOCKER_CONTAINER_PREFIX}_node` (default: `simak_laravel_node`)
- Resource: 1 Core CPU, 1.5GB RAM

## 📝 Common Commands

### Exec ke Container
```bash
# PHP Container
docker exec -it simak_laravel_app bash

# Node Container
docker exec -it simak_laravel_node sh
```

### Artisan Commands
```bash
docker exec -it simak_laravel_app php artisan migrate
docker exec -it simak_laravel_app php artisan cache:clear
```

### Composer
```bash
docker exec -it simak_laravel_app composer install
```

### NPM
```bash
# Install dependencies
docker compose exec node npm install

# Build for production
docker compose exec node npm run build

# Run dev mode (watch mode)
docker compose exec node npm run dev
```

## 🎨 Frontend Development

### Build Tailwind CSS & Vite Assets

Setiap kali ada perubahan pada:
- Tailwind classes di Blade files
- JavaScript/CSS di `resources/` folder
- Livewire components dengan styling baru

**Wajib rebuild assets:**
```bash
docker compose exec node npm run build
```

**Atau jalankan dev mode untuk auto-rebuild:**
```bash
docker compose exec node npm run dev
```

> **💡 Tip:** Gunakan `npm run dev` saat development untuk auto-reload, dan `npm run build` sebelum commit/deploy ke production.

## 🔍 Verifikasi Port

Cek apakah port sudah digunakan:
```bash
# Linux/Mac
lsof -i :8910
lsof -i :9100

# Atau menggunakan netstat
netstat -tulpn | grep 8910
netstat -tulpn | grep 9100
```

## 🎯 Keunggulan Konfigurasi Ini

✅ **Terpusat**: Semua konfigurasi port & nama container di `.env`  
✅ **Fleksibel**: Mudah mengubah port tanpa edit Docker files  
✅ **Anti-Konflik**: Prefix container name mencegah bentrok  
✅ **Sederhana**: Minimal perubahan dari konfigurasi original  

## 🐛 Troubleshooting

### Port Already in Use
```bash
# Cek proses yang menggunakan port
sudo lsof -i :8910

# Ubah port di .env
DOCKER_WEB_PORT=8920
```

### Container Name Conflict
```bash
# Ubah prefix di .env
DOCKER_CONTAINER_PREFIX=simak_laravel_v2
```

### Rebuild After Changes
```bash
docker-compose down
docker-compose up -d --build
```
