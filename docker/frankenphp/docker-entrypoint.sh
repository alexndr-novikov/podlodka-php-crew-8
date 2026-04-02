#!/bin/bash
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    # Install dependencies if vendor is empty
    if [ ! -d /app/vendor ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    # Wait for database if DATABASE_URL is set
    if [ -n "$DATABASE_URL" ]; then
        echo "Waiting for database..."
        timeout=30
        while ! php -r "try { new PDO('$DATABASE_URL'); echo 'ok'; } catch (\Exception \$e) { exit(1); }" 2>/dev/null; do
            timeout=$((timeout - 1))
            if [ $timeout -le 0 ]; then
                echo "Database connection timeout"
                break
            fi
            sleep 1
        done
    fi

    # Run migrations in dev
    if [ "$APP_ENV" = "dev" ]; then
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true
    fi
fi

exec docker-php-entrypoint "$@"
