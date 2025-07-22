# Dockerfile
FROM php:8.1-apache

# Installer extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

# Copier fichiers
COPY . /var/www/html/

# Configuration Apache
RUN chown -R www-data:www-data /var/www/html
RUN a2enmod rewrite

EXPOSE 80