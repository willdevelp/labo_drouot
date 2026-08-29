#!/bin/sh

set -e

PORT="${PORT:-8080}"

echo "======================================"
echo "Laboratoire Drouot"
echo "Starting Apache on port ${PORT}"
echo "======================================"

# ============================================================
# IMPORTANT :
# PHP 8.3 avec mod_php utilise prefork.
# On supprime physiquement les liens event/worker
# juste avant le lancement d'Apache.
# ============================================================

rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf

rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf


# ============================================================
# Vérification MPM
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
# Port Railway
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
# Démarrage
# ============================================================

echo "Starting Apache..."

exec "$@"