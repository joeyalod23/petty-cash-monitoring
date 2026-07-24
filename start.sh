#!/bin/sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force

echo "Running migrations with retry..."
for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do
  php artisan migrate --force 2>&1 && break
  echo "Attempt $i failed, retrying in 5s..."
  sleep 5
done

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
