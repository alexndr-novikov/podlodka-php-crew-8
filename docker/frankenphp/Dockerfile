#syntax=docker/dockerfile:1

# --- Base ---
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

VOLUME /app/var/

# System deps + PHP extensions
# hadolint ignore=DL3008
RUN <<-EOF
    apt-get update
    apt-get install -y --no-install-recommends \
        file \
        git \
        unzip
    install-php-extensions \
        @composer \
        apcu \
        amqp \
        bcmath \
        gd \
        grpc \
        imagick \
        intl \
        opcache \
        opentelemetry \
        pdo_pgsql \
        protobuf \
        redis \
        sockets \
        zip
    rm -rf /var/lib/apt/lists/*
EOF

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
