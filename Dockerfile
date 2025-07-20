# Base image with common dependencies
FROM php:8.3-cli as base

WORKDIR /app/app

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Production image
FROM base as production

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY . .

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

# Test image
FROM base as test

# Install additional test dependencies if needed
RUN apt-get install -y \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Install dependencies including dev packages
RUN composer install --dev --no-cache --prefer-dist

CMD ["php", "bin/phpunit"]