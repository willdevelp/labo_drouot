# syntax=docker/dockerfile:1

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
    unzip \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# 2. Extensions PHP
# ============================================================

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        xml \
        intl


# ============================================================
# 3. Apache
# ============================================================

RUN a2enmod rewrite

# DocumentRoot = /var/www/html/public
RUN sed -ri \
    -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/*.conf

# Autoriser .htaccess
RUN sed -ri \
    -e 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# 4. Application
# ============================================================

WORKDIR /var/www/html

COPY . /var/www/html


# ============================================================
# 5. Uploads
# ============================================================

RUN mkdir -p /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads


# ============================================================
# 6. Entrypoint Railway
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


# ============================================================
# 7. Port Railway
# ============================================================

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]