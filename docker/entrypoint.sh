#!/bin/sh
set -e

echo "==> Almohit Hotels Backend Container Entrypoint"

# Wait for PostgreSQL
if [ -n "$DB_HOST" ]; then
    echo "==> Waiting for database connection at $DB_HOST:${DB_PORT:-5432}..."
    until nc -z "$DB_HOST" "${DB_PORT:-5432}"; do
        sleep 1
    done
    echo "==> Database connection established."
fi

# Ensure storage directories exist with proper structure
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
    echo "==> Creating storage symlink..."
    php artisan storage:link || true
fi

# Run database migrations if explicitly requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force
fi

# Optimization caching in production
if [ "$APP_ENV" = "production" ] || [ "$ENABLE_DOCKER_CACHE" = "true" ]; then
    echo "==> Optimizing application caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "==> Starting application server..."
exec "$@"
