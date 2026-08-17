# Image PHP officielle
FROM php:8.2-cli

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app

# Copier le projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader --prefer-dist




# Exposer le port
EXPOSE $PORT

# Démarrer Laravel
# storage:link --force est relancé à chaque démarrage du conteneur : sur
# Render (sans volume persistant), le filesystem repart de l'image à chaque
# redéploiement/redémarrage, donc le lien symbolique public/storage ->
# storage/app/public doit être recréé à chaque fois, sinon les URLs d'images
# (biens, maintenance) et de PDF (contrats, quittances) renvoient du 404.
CMD php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT