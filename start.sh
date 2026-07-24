#!/bin/sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force 2>/dev/null

echo "Resetting database..."
php artisan db:reset-and-seed 2>&1
if [ $? -ne 0 ]; then
  echo "db:reset-and-seed failed, trying migrate:fresh..."
  php artisan migrate:fresh --force --seed 2>&1
  if [ $? -ne 0 ]; then
    echo "migrate:fresh also failed, trying migrate --force..."
    php artisan migrate --force 2>&1 || echo "All DB attempts failed"
  fi
fi

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
