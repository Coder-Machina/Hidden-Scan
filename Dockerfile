# ══════════════════════════════════════════
# Étape 1 : Build des assets front-end (Vite)
# ══════════════════════════════════════════
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# ══════════════════════════════════════════
# Étape 2 : Image PHP-FPM + Nginx pour la production
# ══════════════════════════════════════════
FROM php:8.4-fpm-alpine

# Installation des dépendances système & extensions PHP
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    sqlite-dev \
    mariadb-client

# Configuration & installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache

# Copie de Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Optimisation du cache Docker pour Composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs

# Copie du code source complet
COPY . .

# Copie des assets compilés par Vite depuis l'étape 1
COPY --from=frontend /app/public/build ./public/build

# Finalisation Composer
RUN composer dump-autoload --optimize

# Installation des dépendances du bot Discord
RUN cd /var/www/html/discord-bot && npm install --omit=dev

# Configuration Nginx, Supervisor, PHP et Entrypoint
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/start-bot.sh /usr/local/bin/start-bot.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/start-bot.sh

# Permissions
RUN mkdir -p /var/www/html/discord-bot/data \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/discord-bot/data \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/discord-bot/data

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
