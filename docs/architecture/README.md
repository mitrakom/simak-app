# Architecture Documentation

Dokumentasi arsitektur sistem aplikasi SIMAK.

## Contents

1. **System Architecture** - Overview arsitektur sistem
2. **Database Schema** - Skema database dan relasi
3. **Design Patterns** - Design patterns yang digunakan
4. **Security Architecture** - Arsitektur keamanan

## System Overview

SIMAK adalah aplikasi berbasis Laravel dengan arsitektur multi-tenant yang menggunakan slug-based routing untuk isolasi institusi.

### Tech Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Livewire v3, Alpine.js, Tailwind CSS v4
- **Database**: MySQL 8
- **Queue**: Redis
- **Cache**: Redis
- **Session**: Database driver
- **Container**: Docker + Docker Compose

### Architecture Principles

1. **Multi-Tenancy**: Setiap institusi memiliki data terpisah dengan routing berbasis slug
2. **MVC Pattern**: Mengikuti pola MVC Laravel standar
3. **Service Layer**: Business logic dipisahkan ke Service classes
4. **Repository Pattern**: Data access melalui Eloquent ORM
5. **Event-Driven**: Menggunakan Laravel Events & Listeners untuk decoupling

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/     # Request handlers
│   ├── Middleware/      # Custom middleware
│   ├── Requests/        # Form validation
│   └── Resources/       # API resources
├── Models/              # Eloquent models
├── Services/            # Business logic
├── Events/              # Event classes
└── Listeners/           # Event listeners

resources/
├── views/
│   ├── components/      # Reusable Blade components
│   ├── auth/           # Authentication views
│   └── livewire/       # Livewire components (Volt)
├── css/                # Tailwind CSS
└── js/                 # JavaScript & Alpine.js

routes/
├── web.php             # Web routes (multi-tenant)
├── api.php             # API routes
└── console.php         # Console commands
```

## Multi-Tenant Architecture

Aplikasi menggunakan pola **shared database with tenant isolation**:

- Single database untuk semua tenant
- Routing: `/{institusi:slug}/...`
- Middleware untuk validasi dan isolasi tenant
- Foreign key `institusi_id` pada semua tabel tenant-specific
