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
# 4. Apache — MPM
# ============================================================

# L'image php:apache peut avoir mpm_event activé.
# PHP avec mod_php nécessite prefork.
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2dismod mpm_prefork || true

# Activer uniquement prefork
RUN a2enmod mpm_prefork

# Module rewrite
RUN a2enmod rewrite

# ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf


# ============================================================
# 5. Vérification MPM
# ============================================================

RUN echo "===== MPM ENABLED =====" \
    && find /etc/apache2/mods-enabled -name 'mpm_*' -print \
    && echo "===== LOADMODULE MPM =====" \
    && grep -R "^LoadModule mpm_" /etc/apache2/mods-enabled || true \
    && echo "========================"


# ============================================================
# 6. DocumentRoot = /public
# ============================================================

RUN sed -ri \
    's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf


# ============================================================
# 7. .htaccess
# ============================================================

RUN sed -ri \
    's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf


# ============================================================
# 8. Application
# ============================================================

WORKDIR /var/www/html

COPY . .


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
# 11. Port Railway
# ============================================================

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]

CMD ["apache2-foreground"]