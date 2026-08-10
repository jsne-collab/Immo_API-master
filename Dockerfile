# Image PHP officielle
FROM php:8.2-cli

# Installer extensions nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app

# Copier ton projet
COPY . .

# Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Commande de démarrage
CMD php artisan serve --host=0.0.0.0 --port=$PORT
