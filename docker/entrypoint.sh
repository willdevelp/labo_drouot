#!/bin/sh
set -e

# Attribution dynamique du port fourni par Railway / Render via $PORT
PORT="${PORT:-8080}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/*.conf

# Re-attribution des droits au dossier d'uploads si un volume persistant est monté
chown -R www-data:www-data /var/www/html/public/uploads 2>/dev/null || true

exec "$@"