# Container web (Laravel 11, PHP-FPM 8.3) + ekstensi PostgreSQL & Redis.
# Salin file ini ke web/Dockerfile setelah `composer create-project`.
FROM php:8.3-fpm
RUN apt-get update && apt-get install -y libpq-dev libzip-dev unzip \
 && docker-php-ext-install pdo pdo_pgsql zip \
 && pecl install redis && docker-php-ext-enable redis
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader || true
