#!/bin/sh
set -e

# Ensure key directories exist
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Try to set ownership to the PHP-FPM user so the runtime can write cached files.
# This runs as root during image start so it will affect mounted volumes too.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# Grant read/write/execute for owner and group on these folders (development friendly)
chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache || true

# Execute default php entrypoint and then the passed CMD (usually php-fpm)
exec docker-php-entrypoint "$@"
