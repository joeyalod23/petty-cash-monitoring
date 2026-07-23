#!/bin/sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force
php artisan migrate --force 2>&1 || echo "Migration warning - continuing startup"
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
