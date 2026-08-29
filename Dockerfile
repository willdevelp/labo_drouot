FROM php:8.3-apache

# ============================================================
# PHP extensions
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
# NETTOYAGE COMPLET DE LA CONFIGURATION MPM
# ============================================================

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf

RUN rm -f /etc/apache2/mods-enabled/mpm_event.* \
          /etc/apache2/mods-enabled/mpm_worker.* \
          /etc/apache2/mods-enabled/mpm_prefork.*


# ============================================================
# ACTIVER UNIQUEMENT PREFORK
# ============================================================

RUN ln -s /etc/apache2/mods-available/mpm_prefork.load \
    /etc/apache2/mods-enabled/mpm_prefork.load

RUN ln -s /etc/apache2/mods-available/mpm_prefork.conf \
    /etc/apache2/mods-enabled/mpm_prefork.conf


# ============================================================
# Apache modules
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
# VERIFICATION MPM
# ============================================================

RUN echo "===== APACHE MPM =====" \
    && grep -R "LoadModule mpm_" /etc/apache2 \
    && echo "======================"


# ============================================================
# ENTRYPOINT
# ============================================================

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]