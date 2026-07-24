#!/bin/sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force

echo "Resetting database..."
php artisan migrate:fresh --force --seed 2>&1

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
