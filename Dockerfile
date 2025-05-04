# Utilise PHP 8.2 FPM Alpine
FROM php:8.2-fpm-alpine

# Variables environnement
ENV APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# Installer dépendances système et PHP extensions
RUN apk update && apk add --no-cache \
    bash git curl icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev zlib-dev \
    nodejs npm build-base \
  && docker-php-ext-configure gd --with-jpeg --with-webp \
  && docker-php-ext-install pdo pdo_mysql gd mbstring exif intl

# Installer Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Copier le code et fixer droits
WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache

# Installer toutes les dépendances (inclut Faker en dev)
RUN composer install --optimize-autoloader \
  && npm install \
  && npm run build

# Exposer le port FPM
EXPOSE 9000
CMD ["php-fpm"]