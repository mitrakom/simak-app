# Gunakan base image PHP 8.3-FPM (sesuaikan jika perlu)
FROM php:8.3-fpm

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

# Expose port 9000 untuk PHP-FPM
EXPOSE 9000

# Copy entrypoint script to handle permissions for mounted volumes on startup
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]