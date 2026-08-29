#!/bin/sh

set -e

PORT="${PORT:-8080}"

echo "======================================"
echo "Laboratoire Drouot"
echo "Starting Apache on port ${PORT}"
echo "======================================"

# ============================================================
# PORT RAILWAY
# ============================================================

echo "Configuring Apache port..."

sed -ri "s/^Listen .*/Listen ${PORT}/" \
    /etc/apache2/ports.conf

sed -ri \
    "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# UPLOADS
# ============================================================

echo "Configuring uploads..."

mkdir -p /var/www/html/public/uploads/patients

chown -R www-data:www-data \
    /var/www/html/public/uploads || true

chmod -R 775 \
    /var/www/html/public/uploads || true


# ============================================================
# MPM
# ============================================================

echo "===== MPM ====="

ls -la /etc/apache2/mods-enabled/ | grep mpm || true

echo "================"


# ============================================================
# TEST CONFIGURATION APACHE
# ============================================================

echo "Testing Apache configuration..."

apache2ctl -t

echo "Apache configuration OK"


# ============================================================
# START APACHE
# ============================================================

echo "Starting Apache..."

exec apache2 -D FOREGROUND