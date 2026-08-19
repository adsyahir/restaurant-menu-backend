#!/bin/sh
set -e

# 1. wait until Postgres accepts TCP connections
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);"; do
  echo "waiting for database..."
  sleep 2
done

# 2. first-boot app key (only if none supplied)
[ -z "${APP_KEY:-}" ] && php artisan key:generate --force || true

# 3. schema + reference data (idempotent seeder)
php artisan migrate --force
php artisan db:seed --class=MalaysiaPostcodeSeeder --force

# 4. cache config + routes for speed
php artisan config:cache
php artisan route:cache

# 5. hand off to the CMD (php-fpm) as PID 1
exec "$@"
