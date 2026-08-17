# Image PHP officielle
FROM php:8.2-cli

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app

# Copier le projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader --prefer-dist


FROM mysql:8
ENV MYSQL_DATABASE=gestion_immo
ENV MYSQL_USER=immo_user
ENV MYSQL_PASSWORD=secret123
ENV MYSQL_ROOT_PASSWORD=root123
EXPOSE 3306


# Exposer le port
EXPOSE $PORT

# Démarrer Laravel
# storage:link --force est relancé à chaque démarrage du conteneur : sur
# Render (sans volume persistant), le filesystem repart de l'image à chaque
# redéploiement/redémarrage, donc le lien symbolique public/storage ->
# storage/app/public doit être recréé à chaque fois, sinon les URLs d'images
# (biens, maintenance) et de PDF (contrats, quittances) renvoient du 404.
CMD php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT