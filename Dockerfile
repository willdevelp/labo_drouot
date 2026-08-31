FROM php:8.3-apache

# ============================================================
# DÉPENDANCES SYSTÈME
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
# EXTENSIONS PHP
# ============================================================
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    mbstring \
    xml \
    intl

# ============================================================
# CONFIGURATION APACHE
# ============================================================
RUN a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# ============================================================
# CODE SOURCE ET PERMISSIONS
# ============================================================
WORKDIR /var/www/html

COPY . .

RUN echo "===== PWA FILES =====" && \
    find /var/www/html/public -type f \
    \( -name "*.png" -o -name "manifest.json" -o -name "sw.js" \) \
    -print

RUN mkdir -p /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads

# ============================================================
# ENTRYPOINT ET PORT
# ============================================================
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]