```dockerfile
# syntax=docker/dockerfile:1

# ============================================================
# Laboratoire Drouot
# PHP 8.3 + Apache
# Railway / Render / PaaS
# ============================================================

FROM php:8.3-apache


# ============================================================
# 1. Dépendances système
# ============================================================

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# 2. Configuration GD
# ============================================================

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg


# ============================================================
# 3. Extensions PHP principales
# ============================================================

RUN docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring


# ============================================================
# 4. Extensions XML
# Installation séquentielle pour éviter les problèmes de
# dépendances pendant le build Railway
# ============================================================

RUN docker-php-ext-install dom

RUN docker-php-ext-install simplexml

RUN docker-php-ext-install xml

RUN docker-php-ext-install xmlreader

RUN docker-php-ext-install xmlwriter


# ============================================================
# 5. Internationalisation
# ============================================================

RUN docker-php-ext-install intl


# ============================================================
# 6. Apache
# ============================================================

RUN a2enmod rewrite


# ============================================================
# 7. DocumentRoot = /public
# ============================================================

RUN sed -ri \
    -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/*.conf


# Autoriser les fichiers .htaccess

RUN sed -ri \
    -e 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# 8. Application
# ============================================================

WORKDIR /var/www/html

COPY . /var/www/html


# ============================================================
# 9. Dossiers d'upload
# ============================================================

RUN mkdir -p \
        /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html/public/uploads \
        -type d \
        -exec chmod 775 {} \;


# ============================================================
# 10. Entrypoint
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


# ============================================================
# 11. Port Railway
# ============================================================

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]
```