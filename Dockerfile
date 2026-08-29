# ============================================================
# Laboratoire Drouot
# PHP 8.3 + Apache
# Railway / Render
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
        libxml2-dev \
        libonig-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# 2. Configuration GD
# ============================================================

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg


# ============================================================
# 3. Extensions PHP
# ============================================================

RUN docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        xml \
        intl


# ============================================================
# 4. Configuration Apache & Modules
# ============================================================

# Suppression stricte des doublons MPM et fixation propre sur prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod mpm_prefork rewrite


# ============================================================
# 5. DocumentRoot = /public
# ============================================================

RUN sed -ri \
    's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/*.conf


# ============================================================
# 6. Autoriser .htaccess
# ============================================================

RUN sed -ri \
    's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# 7. Application
# ============================================================

WORKDIR /var/www/html

COPY . /var/www/html


# ============================================================
# 8. Dossier uploads
# ============================================================

RUN mkdir -p /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads


# ============================================================
# 9. Entrypoint Railway
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


# ============================================================
# 10. Port
# ============================================================

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]