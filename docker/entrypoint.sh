#!/bin/sh

set -e

PORT="${PORT:-8080}"

echo "Starting Apache on port ${PORT}"

# Configuration du port Apache
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

sed -ri \
    "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Permissions uploads
mkdir -p /var/www/html/public/uploads/patients

chown -R www-data:www-data \
    /var/www/html/public/uploads 2>/dev/null || true

chmod -R 775 \
    /var/www/html/public/uploads 2>/dev/null || true

exec "$@"