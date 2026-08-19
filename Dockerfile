# ---- stage 1: resolve PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ---- stage 2: slim runtime ----
FROM php:8.3-fpm-alpine AS app
RUN apk add --no-cache postgresql-dev libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install pdo_pgsql pgsql bcmath pcntl intl zip opcache
# production opcache: don't re-check file timestamps
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.memory_consumption=128'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize \
 && cp docker/entrypoint.sh /usr/local/bin/entrypoint \
 && chmod +x /usr/local/bin/entrypoint \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
