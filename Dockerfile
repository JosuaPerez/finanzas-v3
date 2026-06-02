# 1. Usar el motor oficial de PHP
FROM php:8.2-cli

# 2. Instalar herramientas del sistema, PostgreSQL y librerías de imágenes (GD)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd

# 3. Instalar Composer (el gestor de paquetes de PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Preparar la carpeta de trabajo
WORKDIR /app

# 5. Copiar todo el código de "Finanzas de Combate" a la nube
COPY . .

# 6. Instalar las dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# 7. Encender el radar y arrancar el motor
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}