#!/bin/sh
php artisan key:generate --force
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
