# Gunakan base image PHP 8.3-FPM (sesuaikan jika perlu)
FROM php:8.3-fpm

# Argumen untuk port yang dapat dikonfigurasi
ARG PHP_FPM_PORT=9100

# Set working directory
WORKDIR /var/www/html

# Install dependensi sistem dan ekstensi PHP yang umum untuk Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libgd-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP yang dibutuhkan Laravel + Filament
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml zip

# Install Composer (dependensi manajer PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Konfigurasi PHP-FPM untuk menggunakan port dari environment variable
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-docker.conf && \
    # bind to all interfaces so nginx running in host network can reach php-fpm
    echo "listen = 0.0.0.0:${PHP_FPM_PORT}" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Expose port PHP-FPM (dapat dikonfigurasi via ARG)
EXPOSE ${PHP_FPM_PORT}

CMD ["php-fpm"]