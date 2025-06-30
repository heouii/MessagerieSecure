FROM php:8.2-fpm-alpine

# Variables environnement
ENV APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# Installer dépendances système et extensions PHP
RUN apk update && apk add --no-cache \
    bash git curl icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev zlib-dev \
    libxml2-dev \
    nodejs npm build-base \
  && docker-php-ext-configure gd --with-jpeg --with-webp \
  && docker-php-ext-install \
    pdo pdo_mysql gd mbstring exif intl xml dom

# Installer Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Copier le code
WORKDIR /var/www/html
COPY . .

# Créer et sécuriser les répertoires de cache et de views compilées
RUN mkdir -p storage/framework/cache/data storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 0777 storage bootstrap/cache

# Installer toutes les dépendances PHP (inclut dev : pail, collision) et front
RUN composer install --optimize-autoloader --prefer-dist --no-interaction \
  && npm install \
  && npm run build

  # Installer symfony/mailgun-mailer
RUN composer require symfony/mailgun-mailer

# Exposer le port FPM
EXPOSE 9000

# Lancement de PHP-FPM en ajustant les permissions à chaque démarrage
CMD ["sh", "-c", "chown -R www-data:www-data storage bootstrap/cache && php-fpm"]
