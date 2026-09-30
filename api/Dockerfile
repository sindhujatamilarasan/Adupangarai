FROM php:8.4-cli-alpine
# gd: only for the photo upload tests (fake images)
RUN apk add --no-cache postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev libwebp-dev unzip \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo_pgsql zip gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
