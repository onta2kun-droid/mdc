#!/bin/sh
set -e

# Tunggu ringan (berjaga-jaga bila nanti pakai MySQL/Postgres container)
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
  echo "Menunggu database di $DB_HOST..."
  for i in $(seq 1 30); do
    php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo "ok"; exit(0); } catch (Exception $e) { sleep(1); }'
  done
fi

# Migrasi otomatis saat container mulai (idempoten)
php artisan migrate --force

exec "$@"
