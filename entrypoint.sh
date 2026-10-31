#!/bin/sh
set -e

# Dependencies (first install: the project is mounted as a volume, so vendor/ comes from the host)
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

php tests/Application/bin/console assets:install tests/Application/public

# Assets of the shadcn theme of the demo: importmap packages and Tailwind CSS build
APP_THEME=shadcn php tests/Application/bin/console importmap:install
APP_THEME=shadcn php tests/Application/bin/console tailwind:build

# Database of each theme (SQLite) with the demo data set
for theme in bootstrap shadcn; do
    APP_THEME=$theme php tests/Application/bin/console doctrine:schema:update --force
    APP_THEME=$theme php tests/Application/bin/console app:load-books --if-empty
done

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
