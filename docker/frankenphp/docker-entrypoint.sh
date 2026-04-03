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
        while ! php -r "
            \$url = parse_url('$DATABASE_URL');
            \$dsn = 'pgsql:host=' . \$url['host'] . ';port=' . (\$url['port'] ?? 5432) . ';dbname=' . ltrim(\$url['path'], '/');
            try { new PDO(\$dsn, \$url['user'], \$url['pass']); echo 'ok'; } catch (\Exception \$e) { exit(1); }
        " 2>/dev/null; do
            timeout=$((timeout - 1))
            if [ $timeout -le 0 ]; then
                echo "Database connection timeout"
                break
            fi
            sleep 1
        done
    fi

    # Run migrations in dev (only if Doctrine is installed)
    if [ "$APP_ENV" = "dev" ] && php bin/console list doctrine:migrations 2>/dev/null | grep -q migrate; then
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true
    fi
fi

exec docker-php-entrypoint "$@"
