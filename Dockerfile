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
# 2. GD
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
# 4. Apache : UN SEUL MPM
# ============================================================

RUN find /etc/apache2/mods-enabled \
        -type l \
        -name 'mpm_*' \
        -delete \
    && find /etc/apache2/mods-enabled \
        -type f \
        -name 'mpm_*' \
        -delete

RUN ln -sf /etc/apache2/mods-available/mpm_prefork.load \
        /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf \
        /etc/apache2/mods-enabled/mpm_prefork.conf

RUN a2enmod rewrite

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf


# ============================================================
# 5. DocumentRoot = /var/www/html/public
# ============================================================

RUN sed -ri \
    's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# 6. .htaccess
# ============================================================

RUN sed -ri \
    's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# 7. Vérification Apache
# ============================================================

RUN echo "===== MPM CONFIGURATION =====" \
    && apache2ctl -M 2>&1 | grep mpm \
    && echo "============================="


# ============================================================
# 8. Application
# ============================================================

WORKDIR /var/www/html

COPY . /var/www/html


# ============================================================
# 9. Uploads
# ============================================================

RUN mkdir -p /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads


# ============================================================
# 10. Entrypoint
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


# ============================================================
# 11. Port
# ============================================================

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]