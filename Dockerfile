# syntax=docker/dockerfile:1

# ─────────────────────────────────────────────────────────────
# Laboratoire Drouot — image Docker (Railway, Render, ou tout PaaS
# supportant le déploiement par Dockerfile)
# ─────────────────────────────────────────────────────────────
# PHP 8.3 + Apache (mod_php). Le dossier /public devient le DocumentRoot
# directement (pas de dossier /public/ visible dans les URLs, pas besoin
# du .htaccess racine qui gère ça en local sur WAMP/XAMPP).
FROM php:8.3-apache

# ── Dépendances système ────────────────────────────────────
# Nécessaires aux extensions PHP utilisées par PhpSpreadsheet (import
# Excel des assurés) et par le rendu d'images (GD).
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# ── Extensions PHP ──────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        xml \
        simplexml \
        dom \
        xmlreader \
        xmlwriter \
        intl

# ── Apache ───────────────────────────────────────────────────
# mod_rewrite pour le .htaccess de /public (routage vers index.php),
# AllowOverride All pour que ce .htaccess soit effectivement pris en
# compte, et DocumentRoot pointé directement sur /public.
RUN a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' \
        /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

# ── Dossier d'upload (photos patients) ──────────────────────
# ATTENTION : le disque du conteneur est éphémère par défaut sur la
# plupart des PaaS — tout fichier écrit ici est perdu au prochain
# déploiement ou redémarrage du service. Pour conserver les photos :
#   - Railway : attacher un Volume (Settings → Volumes) monté sur
#     /var/www/html/public/uploads. Note : Railway monte les volumes en
#     tant que root, donc il peut être nécessaire d'ajuster les
#     permissions au démarrage (voir docker/entrypoint.sh) si www-data
#     n'arrive pas à y écrire une fois le volume attaché.
#   - Render : Persistent Disk (Dashboard → ton service → Disks).
RUN mkdir -p public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html/public/uploads -type d -exec chmod 775 {} \;

# ── Port dynamique fourni par Render ────────────────────────
# Render assigne le port d'écoute via la variable d'environnement $PORT
# (pas fixe), donc on l'applique à Apache au démarrage du conteneur,
# pas au moment du build.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
