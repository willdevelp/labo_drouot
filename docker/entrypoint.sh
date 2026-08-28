#!/bin/sh
# Render, Railway (et la plupart des PaaS) fournissent le port d'écoute
# via la variable d'environnement $PORT, qui varie selon l'environnement
# (jamais fixée à 80/8080 par avance). On l'applique à la configuration
# Apache juste avant de démarrer, à CHAQUE lancement du conteneur — pas
# au moment du build, car $PORT n'existe pas encore à ce moment-là.
set -e

PORT="${PORT:-8080}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf

# Si un volume persistant (Railway, etc.) est monté sur le dossier
# d'upload, il arrive en écriture root — on redonne la main à www-data
# à chaque démarrage pour que l'appli puisse toujours y écrire.
chown -R www-data:www-data /var/www/html/public/uploads 2>/dev/null || true

exec "$@"
