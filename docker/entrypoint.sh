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

# Remplace tout bloc Listen existant
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Remplace le port du VirtualHost 000-default.conf
sed -ri "s/<VirtualHost [^>]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ============================================================
# NETTOYAGE DES MODULES MPM
# ============================================================
echo "Cleaning MPM..."

rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

echo "===== ACTIVE MPM ====="
find /etc/apache2/mods-enabled -maxdepth 1 -name 'mpm_*' -print
echo "======================"

# ============================================================
# CRÉATION DES DOSSIERS ET PERMISSIONS
# ============================================================
echo "Configuring uploads..."

mkdir -p /var/www/html/public/uploads/patients
chown -R www-data:www-data /var/www/html/public/uploads || true
chmod -R 775 /var/www/html/public/uploads || true

# ============================================================
# TEST ET DÉMARRAGE APACHE
# ============================================================
echo "Testing Apache configuration..."
apache2ctl -t

echo "Starting Apache..."
exec apache2-foreground