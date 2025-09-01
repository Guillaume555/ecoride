# 1) Base PHP 8.1 + Apache
FROM php:8.1-apache

# 2) Paquets utiles (git/unzip pour Composer, SSL pour PECL)
RUN apt-get update && apt-get install -y \
    git unzip libssl-dev pkg-config \
 && rm -rf /var/lib/apt/lists/*

# 3) Extensions PHP natives
RUN docker-php-ext-install pdo pdo_mysql

# 4) Extension MongoDB (native) + activation
RUN pecl install mongodb \
 && docker-php-ext-enable mongodb

# 5) Apache (réécritures si tu as des routes)
RUN a2enmod rewrite

# 6) Composer (depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 7) Répertoire de travail
WORKDIR /var/www/html

# 8) Copier le code dans l'image
COPY . .

# 9) Installer dépendances PHP (prod)
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# 10) Droits (selon besoin)
RUN chown -R www-data:www-data /var/www/html

# 11) Port web
EXPOSE 80
