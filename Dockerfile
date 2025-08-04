FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libonig-dev \
    libzip-dev \
    curl \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader
