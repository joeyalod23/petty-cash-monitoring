#!/bin/sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force 2>/dev/null

echo "Resetting database (with fresh DB connection)..."
php artisan db:reset-and-seed 2>&1 || php artisan migrate:fresh --force --seed 2>&1 || echo "DB reset failed - will retry on next request"

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
