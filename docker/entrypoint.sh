#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure directories exist
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache

# Ensure PsySH config directory exists and is writable for tinker (www-data home is /var/www)
mkdir -p /var/www/.config/psysh || true
chgrp -R www-data /var/www/.config || true
chmod -R ug+rw /var/www/.config || true

# Permissions (idempotent, tolerate RO filesystems)
chgrp -R www-data storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

# If APP_KEY is missing and we can write .env, try to generate
if [ -z "${APP_KEY:-}" ]; then
  if [ -f .env ] && grep -q '^APP_KEY=' .env; then
    if [ -w .env ]; then
      php artisan key:generate --no-interaction || true
    fi
  fi
fi

# Cache config/routes/views for production
php artisan config:clear --no-interaction || true
php artisan route:clear --no-interaction || true
php artisan view:clear --no-interaction || true

php artisan config:cache --no-interaction || true
php artisan route:cache --no-interaction || true
php artisan view:cache --no-interaction || true

# Create storage symlink for public files
php artisan storage:link --no-interaction || true

# Optional database migrations
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force --no-interaction || true
fi

exec "$@"
