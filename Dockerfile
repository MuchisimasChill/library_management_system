FROM php:8.3-cli

WORKDIR /app/app

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]