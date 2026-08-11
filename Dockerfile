FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .
RUN php artisan config:clear && php artisan route:clear
RUN composer install --no-dev --optimize-autoloader --prefer-dist

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=$PORT