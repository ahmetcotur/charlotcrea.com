#!/bin/sh
set -eu
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public/uploads
chown -R www-data:www-data storage bootstrap/cache
if [ "$1" = "charlot-web" ]; then
    gosu www-data php artisan charlot:initialize-deployment
    exec php-fpm
fi
if [ "$1" = "php-fpm" ]; then
    exec "$@"
fi
exec gosu www-data "$@"
