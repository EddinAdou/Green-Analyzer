FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    netcat-openbsd \
    && docker-php-ext-install pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install

# Copier le script d'initialisation
COPY init.sh /usr/local/bin/

# Rendre le script exécutable
RUN chmod +x /usr/local/bin/init.sh

# Exposer le port 9000
EXPOSE 9000
# Exécuter le script d'initialisation au démarrage
CMD ["/usr/local/bin/init.sh"]