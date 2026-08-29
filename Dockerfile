FROM php:8.3-apache

# ============================================================
# DEPENDANCES
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
# GD
# ============================================================

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg


# ============================================================
# PHP
# ============================================================

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
# APACHE
# ============================================================

RUN a2enmod rewrite

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf


# ============================================================
# DOCUMENT ROOT
# ============================================================

RUN sed -ri \
    's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# HTACCESS
# ============================================================

RUN sed -ri \
    's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# APPLICATION
# ============================================================

WORKDIR /var/www/html

COPY . .


# ============================================================
# UPLOADS
# ============================================================

RUN mkdir -p /var/www/html/public/uploads/patients \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads


# ============================================================
# VERIFICATION
# ============================================================

RUN echo "===== APACHE MPM =====" \
    && apache2ctl -M 2>&1 | grep mpm || true

RUN apache2ctl -t


# ============================================================
# ENTRYPOINT
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]