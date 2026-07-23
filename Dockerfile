FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libpq-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x start.sh

EXPOSE 8000

CMD ["./start.sh"]
