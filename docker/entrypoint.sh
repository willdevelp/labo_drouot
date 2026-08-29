#!/bin/sh

set -e

PORT="${PORT:-8080}"

echo "======================================"
echo "Laboratoire Drouot"
echo "Starting Apache on port ${PORT}"
echo "======================================"


# ============================================================
# Configuration du port Railway
# ============================================================

sed -ri "s/^Listen .*/Listen ${PORT}/" \
    /etc/apache2/ports.conf

sed -ri \
    "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# Uploads
# ============================================================

mkdir -p /var/www/html/public/uploads/patients

chown -R www-data:www-data \
    /var/www/html/public/uploads 2>/dev/null || true

chmod -R 775 \
    /var/www/html/public/uploads 2>/dev/null || true


# ============================================================
# Vérification
# ============================================================

echo "===== MPM FINAL ====="

find /etc/apache2/mods-enabled \
    -name 'mpm_*' \
    -print

echo "===== LOADMODULE FINAL ====="

grep -R "^LoadModule mpm_" \
    /etc/apache2/mods-enabled 2>/dev/null || true

echo "======================"


# ============================================================
# Démarrage
# ============================================================

exec "$@"