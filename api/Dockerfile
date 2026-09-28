FROM php:8.4-cli-alpine
RUN apk add --no-cache postgresql-dev libzip-dev unzip \
 && docker-php-ext-install pdo_pgsql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
