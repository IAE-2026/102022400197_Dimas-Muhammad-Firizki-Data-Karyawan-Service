#!/usr/bin/env sh
set -e

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan lighthouse:clear-cache

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --ssl=0 --silent; do
  echo "Waiting for database..."
  sleep 2
done

php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan lighthouse:clear-cache
php artisan serve --host=0.0.0.0 --port=8000
