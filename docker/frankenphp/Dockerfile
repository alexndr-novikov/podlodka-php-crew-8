# syntax=docker/dockerfile:1

# --- Base ---
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

VOLUME /app/var/

# System deps
# hadolint ignore=DL3008
RUN <<-EOF
    apt-get update
    apt-get install -y --no-install-recommends \
        file \
        git \
        unzip
    rm -rf /var/lib/apt/lists/*
EOF

# PHP extensions — core
RUN install-php-extensions \
    @composer \
    apcu \
    bcmath \
    gd \
    intl \
    opcache \
    pdo_pgsql \
    redis \
    sockets \
    zip

# PHP extensions — additional (heavier builds)
RUN install-php-extensions \
    imagick

# PHP extensions — observability (uncomment when needed, grpc takes ~10min to compile)
# RUN install-php-extensions \
#     grpc \
#     protobuf \
#     opentelemetry

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# --- Dev ---
FROM base AS dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off
ENV FRANKENPHP_WORKER_CONFIG=watch

RUN <<-EOF
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
    install-php-extensions xdebug pcov
    rm -rf /var/lib/apt/lists/*
    git config --system --add safe.directory /app
EOF

COPY --link docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch"]

# --- Prod ---
FROM base AS prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install dependencies (no dev packages, optimized autoloader)
COPY --link composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --prefer-dist --no-progress

# Copy application code
COPY --link . /app

RUN <<-EOF
    composer dump-autoload --classmap-authoritative --no-dev
    php bin/console cache:warmup
    chown -R www-data:www-data /app/var
EOF

USER www-data

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
