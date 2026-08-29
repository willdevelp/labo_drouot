#!/bin/sh

set -e

echo "======================================"
echo "Laboratoire Drouot"
echo "Custom entrypoint started"
echo "======================================"

PORT="${PORT:-8080}"

echo "Railway PORT = ${PORT}"


# ============================================================
# PORT APACHE
# ============================================================

echo "Configuring Apache port..."

sed -ri "s/^Listen .*/Listen ${PORT}/" \
    /etc/apache2/ports.conf

sed -ri \
    "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# MPM
# ============================================================

echo "Cleaning MPM..."

rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf

rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf


echo "===== ACTIVE MPM ====="

find /etc/apache2/mods-enabled \
    -maxdepth 1 \
    -name 'mpm_*' \
    -print

echo "======================"


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
# TEST APACHE
# ============================================================

echo "Testing Apache configuration..."

apache2ctl -t

echo "Apache configuration OK"


# ============================================================
# START
# ============================================================

echo "Starting Apache..."

exec apache2-foreground