# syntax=docker/dockerfile:1
FROM php:8.3-apache AS php-base
RUN --mount=type=secret,id=build_ca,target=/etc/ssl/certs/ca-certificates.crt,mode=0444 \
    sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources \
    && apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libsqlite3-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_pgsql pdo_sqlite zip gd mbstring intl bcmath opcache pcntl \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

FROM php-base AS backend
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN --mount=type=secret,id=build_ca,target=/etc/ssl/certs/ca-certificates.crt,mode=0444 \
    COMPOSER_CAFILE=/etc/ssl/certs/ca-certificates.crt composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

FROM node:22-alpine AS frontend
WORKDIR /app
ENV PUPPETEER_SKIP_DOWNLOAD=true VITE_APP_NAME=FinanzasRPG
COPY package.json package-lock.json ./
RUN --mount=type=secret,id=build_ca,target=/etc/ssl/certs/ca-certificates.crt,mode=0444 \
    NODE_EXTRA_CA_CERTS=/etc/ssl/certs/ca-certificates.crt npm ci
COPY . .
COPY --from=backend /var/www/html/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false APP_NAME=FinanzasRPG APP_LOCALE=es APP_FALLBACK_LOCALE=es \
    DB_CONNECTION=pgsql DB_SSLMODE=require CACHE_STORE=database SESSION_DRIVER=database \
    SESSION_SECURE_COOKIE=true QUEUE_CONNECTION=sync LOG_CHANNEL=stderr
COPY --from=backend /var/www/html /var/www/html
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/public/sw.js /app/public/workbox-*.js ./public/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /usr/local/bin/start-finanzas
RUN a2enmod rewrite headers && chmod +x /usr/local/bin/start-finanzas \
    && chmod -R a+rX /var/www/html \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8080
CMD ["start-finanzas"]
