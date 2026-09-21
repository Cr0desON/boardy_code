FROM php:8.3-fpm-bookworm

# Системные пакеты
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    zip unzip libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP-расширения
RUN docker-php-ext-install pdo_mysql mbstring zip opcache
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Сначала только composer.json — для кеширования слоя зависимостей
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Потом весь код
COPY . .
RUN composer dump-autoload --optimize

# Права
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
