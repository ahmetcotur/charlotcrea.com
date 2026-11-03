FROM php:8.5-fpm-bookworm@sha256:75dc898a8861a777f14c57b2afbb811a848735701593cbc354e4194807a88f3e AS application
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip gosu libpq-dev libsqlite3-dev libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_sqlite gd intl zip bcmath exif pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2@sha256:5190d9b445add2fa26090108273cb1ef1e1af98175201c737b85b3c4f90fbf7c /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public/uploads \
    && rm -f bootstrap/cache/*.php \
    && composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan charlot:assets \
    && php artisan storage:link \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public/uploads \
    && chown -R www-data:www-data storage bootstrap/cache
COPY deployment/laravel/php.ini /usr/local/etc/php/conf.d/charlot.ini
COPY deployment/laravel/entrypoint.sh /usr/local/bin/charlot-entrypoint
RUN chmod +x /usr/local/bin/charlot-entrypoint
ENTRYPOINT ["charlot-entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.28-alpine@sha256:a8b39bd9cf0f83869a2162827a0caf6137ddf759d50a171451b335cecc87d236 AS web
WORKDIR /var/www/html
COPY --from=application /var/www/html/public ./public
COPY --from=application /var/www/html/dist/assets ./dist/assets
COPY deployment/laravel/nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/up || exit 1
