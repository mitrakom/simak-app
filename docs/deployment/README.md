# Deployment Documentation

Panduan deployment aplikasi SIMAK ke production.

## Contents

1. **Production Deployment** - Deploy ke production server
2. **Docker Deployment** - Deploy menggunakan Docker
3. **Server Configuration** - Konfigurasi server
4. **Monitoring & Maintenance** - Monitoring dan maintenance

## Prerequisites

- Linux server (Ubuntu 20.04+ recommended)
- Docker & Docker Compose installed
- Domain name configured
- SSL certificate (Let's Encrypt recommended)

## Quick Deploy with Docker

```bash
# Clone repository
git clone <repository-url>
cd simak-app

# Copy environment file
cp .env.example .env

# Edit .env dengan konfigurasi production
nano .env

# Build dan run containers
docker compose -f docker-compose.prod.yml up -d --build

# Generate app key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate --force

# Cache config dan routes
docker compose exec app php artisan optimize
```

## Environment Configuration

Pastikan konfigurasi berikut di `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=db
DB_DATABASE=simak
DB_USERNAME=simak_user
DB_PASSWORD=<strong-password>

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
```

## Security Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Generate strong `APP_KEY`
- [ ] Use strong database passwords
- [ ] Configure firewall (allow only 80, 443, 22)
- [ ] Setup SSL/TLS certificate
- [ ] Enable rate limiting
- [ ] Configure backup strategy
- [ ] Setup monitoring & logging

## Backup Strategy

```bash
# Database backup
docker compose exec db mysqldump -u user -p database > backup.sql

# Full backup including files
tar -czf simak-backup-$(date +%Y%m%d).tar.gz \
  storage/ \
  .env \
  backup.sql
```

## Monitoring

- Use Laravel Telescope for debugging (development only)
- Configure Laravel Horizon for queue monitoring
- Setup log monitoring (e.g., Sentry, Papertrail)
- Monitor server resources (CPU, Memory, Disk)
