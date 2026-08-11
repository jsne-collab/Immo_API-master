FROM php:8.2-cli

# Installer dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring


# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app

# Copier le code source
COPY . .

# Installer dépendances Laravel (sans dev)
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Nettoyer le cache Laravel
RUN php artisan config:clear && php artisan route:clear && php artisan cache:clear

# Exposer le port
EXPOSE 8000

# Commande de démarrage
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
