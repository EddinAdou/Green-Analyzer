#!/bin/bash

# Attendre que le service de base de données soit prêt
until nc -z -v -w30 db 3306
do
  echo "Waiting for database connection..."
  sleep 1
done

# Créer la base de données
php bin/console doctrine:database:create

# Vérifier les migrations en attente
PENDING_MIGRATIONS=$(php bin/console doctrine:migrations:status --show-versions | grep 'New Migrations')

if [ -z "$PENDING_MIGRATIONS" ]; then
  echo "No pending migrations found. Creating a new migration..."
  php bin/console make:migration
else
  echo "Pending migrations found. Skipping migration creation."
fi

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Démarrer php-fpm
php-fpm