#!/bin/sh
set -e

echo "Starting Erles Bakery Backend..."

# Run database migrations if enabled or on first run
if [ "$AUTO_MIGRATE" = "true" ] || [ "$APP_ENV" = "production" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || echo "Migration warning: could not run migrations immediately, check DB connection."
    
    if [ "$AUTO_SEED" = "true" ]; then
        echo "Seeding initial database records..."
        php artisan db:seed --force --no-interaction || echo "Seeder warning: seed skipped or already present."
    fi
fi

# Clear and optimize config cache
php artisan config:clear || true
php artisan route:clear || true

PORT_TO_USE="${PORT:-8000}"
echo "Serving application on port $PORT_TO_USE..."
exec php artisan serve --host=0.0.0.0 --port="$PORT_TO_USE"

