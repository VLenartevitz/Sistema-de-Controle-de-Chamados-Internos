#!/bin/bash
set -e

# Wait for MySQL if using mysql connection
if [ "$DB_CONNECTION" = "mysql" ]; then
  echo "Waiting for MySQL at $DB_HOST:$DB_PORT..."
  max_tries=30
  tries=0
  until mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" --silent 2>/dev/null; do
    tries=$((tries+1))
    if [ $tries -ge $max_tries ]; then
      echo "MySQL not reachable after $max_tries tries, continuing..."
      break
    fi
    echo "MySQL not ready, retry $tries/$max_tries..."
    sleep 2
  done
  echo "MySQL ready or timeout reached."
fi

# Ensure .env exists
if [ ! -f .env ]; then
  echo "Creating .env from .env.example..."
  cp .env.example .env
fi

# Generate key if missing
if ! grep -q "APP_KEY=base64" .env; then
  echo "Generating APP_KEY..."
  php artisan key:generate --force || true
fi

# Run migrations (only if DB is reachable)
if [ "$SKIP_MIGRATIONS" != "true" ]; then
  echo "Running migrations..."
  php artisan migrate --force || echo "migrate failed, will retry on next start"
fi

# Clear caches
php artisan config:clear || true

exec "$@"
