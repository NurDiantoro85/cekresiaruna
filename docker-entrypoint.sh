#!/bin/bash
set -e

# Support dynamic PORT for Render / Railway (default: 80)
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Warning: APP_KEY is not set. Generating application key..."
    php artisan key:generate --force
fi

# Ensure storage link exists
php artisan storage:link || true

# Run database migrations if configured
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Run database seeder if configured (to seed initial variants & stores)
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
fi

# Optimize Laravel cache for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Apache web server on port $PORT..."
exec apache2-foreground
