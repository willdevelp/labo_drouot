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
    /var/www/html/public/uploads 2>/dev/null || true

chmod -R 775 \
    /var/www/html/public/uploads 2>/dev/null || true


# ============================================================
# MPM
# ============================================================

echo "Cleaning Apache MPM configuration..."

rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf

rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf


echo "===== MPM ====="

ls -la /etc/apache2/mods-enabled/ | grep mpm || true

echo "================"


# ============================================================
# DÉMARRAGE
# ============================================================

echo "Starting Apache..."

exec apache2-foreground