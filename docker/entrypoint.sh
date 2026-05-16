#!/usr/bin/env sh
set -e

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --ssl=0 --silent; do
  echo "Waiting for database..."
  sleep 2
done

php artisan config:clear
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
