# Docker Infrastructure — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the complete Docker infrastructure so that `make up` brings up all services — Traefik, FrankenPHP, PostgreSQL, Valkey, Meilisearch, Mailpit, LocalStack, Temporal, Grafana LGTM, Buggregator — with HTTPS on `*.workshop.localhost`.

**Architecture:** Traefik v3 as TLS-terminating entry point with mkcert wildcard certificates. FrankenPHP (dunglas image, PHP 8.4, Bookworm) as app server behind Traefik. Modular compose files in `compose/` directory connected via `include:`. Profiles for optional services. Makefile as the developer entry point.

**Tech Stack:** Docker Compose V2, Traefik v3, FrankenPHP (dunglas/frankenphp), PostgreSQL 17, Valkey 8, Meilisearch v1, Mailpit, LocalStack, Temporal, Grafana LGTM, Cloudflared, mkcert

---

### Task 1: Project scaffold and .env

**Files:**
- Create: `.env`
- Create: `.env.local` (gitignored, template)
- Create: `.gitignore`
- Create: `.dockerignore`

- [ ] **Step 1: Create `.gitignore`**

```gitignore
###> symfony/framework-bundle ###
/.env.local
/.env.local.php
/.env.*.local
/config/secrets/dev/dev.decrypt.private.php
/public/bundles/
/var/
/vendor/
###< symfony/framework-bundle ###

# Docker
docker/traefik/certs/*.pem
docker/traefik/certs/*.key

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Node (Tailwind)
/node_modules/
```

- [ ] **Step 2: Create `.dockerignore`**

```dockerignore
.git
.github
.idea
.vscode
.env.local
docker/traefik/certs
node_modules
var
docs
old
*.md
!README.md
```

- [ ] **Step 3: Create `.env`**

```env
# Compose
COMPOSE_PROJECT_NAME=workshop

# App
APP_ENV=dev
APP_SECRET=workshop-secret-change-me
SERVER_NAME=app.workshop.localhost

# PostgreSQL
POSTGRES_USER=workshop
POSTGRES_PASSWORD=workshop
POSTGRES_DB=workshop
POSTGRES_VERSION=17

# Valkey
VALKEY_URL=redis://valkey:6379

# Meilisearch
MEILI_MASTER_KEY=workshop-meili-key
MEILI_URL=http://meilisearch:7700

# Mailpit
MAILER_DSN=smtp://mailpit:1025

# LocalStack
AWS_ENDPOINT=http://localstack:4566
AWS_ACCESS_KEY_ID=workshop
AWS_SECRET_ACCESS_KEY=workshop
AWS_DEFAULT_REGION=us-east-1
S3_BUCKET=workshop-uploads

# Temporal
TEMPORAL_ADDRESS=temporal:7233
TEMPORAL_NAMESPACE=default

# OpenTelemetry
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-lgtm:4318
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
OTEL_SERVICE_NAME=workshop-app
OTEL_PHP_AUTOLOAD_ENABLED=true

# Tunnel (optional, for .env.local)
# NGROK_AUTHTOKEN=your-token-here
```

- [ ] **Step 4: Commit**

```bash
git add .gitignore .dockerignore .env
git commit -m "Add project scaffold: .gitignore, .dockerignore, .env"
```

---

### Task 2: Traefik with mkcert TLS

**Files:**
- Create: `docker/traefik/traefik.yml`
- Create: `docker/traefik/dynamic.yml`
- Create: `docker/traefik/certs/.gitkeep`
- Create: `compose/proxy.yml`

- [ ] **Step 1: Create Traefik static config**

Create `docker/traefik/traefik.yml`:

```yaml
api:
  dashboard: true
  insecure: false

entryPoints:
  web:
    address: ":80"
    http:
      redirections:
        entryPoint:
          to: websecure
          scheme: https
  websecure:
    address: ":443"

providers:
  docker:
    endpoint: "unix:///var/run/docker.sock"
    exposedByDefault: false
    network: workshop
  file:
    filename: /etc/traefik/dynamic.yml
    watch: true

log:
  level: INFO
```

- [ ] **Step 2: Create Traefik dynamic TLS config**

Create `docker/traefik/dynamic.yml`:

```yaml
tls:
  certificates:
    - certFile: /etc/certs/local-cert.pem
      keyFile: /etc/certs/local-key.pem
  stores:
    default:
      defaultCertificate:
        certFile: /etc/certs/local-cert.pem
        keyFile: /etc/certs/local-key.pem
```

- [ ] **Step 3: Create certs directory placeholder**

```bash
mkdir -p docker/traefik/certs
touch docker/traefik/certs/.gitkeep
```

- [ ] **Step 4: Create `compose/proxy.yml`**

```yaml
services:
  traefik:
    image: traefik:v3.3
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./docker/traefik/traefik.yml:/etc/traefik/traefik.yml:ro
      - ./docker/traefik/dynamic.yml:/etc/traefik/dynamic.yml:ro
      - ./docker/traefik/certs:/etc/certs:ro
    networks:
      - workshop
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.traefik-dashboard.rule=Host(`traefik.workshop.localhost`)"
      - "traefik.http.routers.traefik-dashboard.entrypoints=websecure"
      - "traefik.http.routers.traefik-dashboard.tls=true"
      - "traefik.http.routers.traefik-dashboard.service=api@internal"
    healthcheck:
      test: ["CMD", "traefik", "healthcheck"]
      interval: 10s
      timeout: 5s
      retries: 3

networks:
  workshop:
    name: workshop
```

- [ ] **Step 5: Commit**

```bash
git add docker/traefik/ compose/proxy.yml
git commit -m "Add Traefik v3 with mkcert TLS configuration"
```

---

### Task 3: FrankenPHP Dockerfile

**Files:**
- Create: `docker/frankenphp/Dockerfile`
- Create: `docker/frankenphp/Caddyfile`
- Create: `docker/frankenphp/conf.d/10-app.ini`
- Create: `docker/frankenphp/conf.d/20-app.dev.ini`
- Create: `docker/frankenphp/docker-entrypoint.sh`

- [ ] **Step 1: Create Caddyfile**

Create `docker/frankenphp/Caddyfile`:

```
{
    {$CADDY_GLOBAL_OPTIONS}

    frankenphp {
        {$FRANKENPHP_CONFIG}
    }

    order php_server before file_server
}

{$SERVER_NAME:localhost} {
    log {
        level {$CADDY_LOG_LEVEL:INFO}
    }

    root * /app/public
    encode zstd br gzip

    {$CADDY_SERVER_EXTRA_DIRECTIVES}

    php_server {
        worker {
            file ./public/index.php
            {$FRANKENPHP_WORKER_CONFIG}
        }
    }
}
```

- [ ] **Step 2: Create PHP ini configs**

Create `docker/frankenphp/conf.d/10-app.ini`:

```ini
; Base PHP configuration
expose_php = 0
date.timezone = UTC
memory_limit = 256M
post_max_size = 64M
upload_max_filesize = 64M

; OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

Create `docker/frankenphp/conf.d/20-app.dev.ini`:

```ini
; Dev overrides
opcache.validate_timestamps = 1
opcache.revalidate_freq = 0
```

- [ ] **Step 3: Create docker-entrypoint.sh**

Create `docker/frankenphp/docker-entrypoint.sh`:

```bash
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
```

- [ ] **Step 4: Create Dockerfile**

Create `docker/frankenphp/Dockerfile`:

```dockerfile
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
```

- [ ] **Step 5: Commit**

```bash
git add docker/frankenphp/
git commit -m "Add FrankenPHP Dockerfile with multi-stage build"
```

---

### Task 4: Compose modules — app, database, cache

**Files:**
- Create: `compose/app.yml`
- Create: `compose/database.yml`
- Create: `compose/cache.yml`
- Create: `docker/postgres/init.sql`

- [ ] **Step 1: Create `docker/postgres/init.sql`**

```sql
-- Create Temporal database
SELECT 'CREATE DATABASE temporal'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'temporal')\gexec

SELECT 'CREATE DATABASE temporal_visibility'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'temporal_visibility')\gexec
```

- [ ] **Step 2: Create `compose/database.yml`**

```yaml
services:
  postgres:
    image: postgres:17
    restart: unless-stopped
    environment:
      POSTGRES_USER: ${POSTGRES_USER:-workshop}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-workshop}
      POSTGRES_DB: ${POSTGRES_DB:-workshop}
    volumes:
      - postgres-data:/var/lib/postgresql/data
      - ./docker/postgres/init.sql:/docker-entrypoint-initdb.d/init.sql:ro
    networks:
      - workshop
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-workshop}"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  postgres-data:

networks:
  workshop:
    external: true
```

- [ ] **Step 3: Create `compose/cache.yml`**

```yaml
services:
  valkey:
    image: valkey/valkey:8
    restart: unless-stopped
    volumes:
      - valkey-data:/data
    networks:
      - workshop
    healthcheck:
      test: ["CMD", "valkey-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  valkey-data:

networks:
  workshop:
    external: true
```

- [ ] **Step 4: Create `compose/app.yml`**

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/frankenphp/Dockerfile
      target: dev
    restart: unless-stopped
    environment:
      SERVER_NAME: ":8080"
      APP_ENV: ${APP_ENV:-dev}
      APP_SECRET: ${APP_SECRET:-workshop-secret-change-me}
      DATABASE_URL: "postgresql://${POSTGRES_USER:-workshop}:${POSTGRES_PASSWORD:-workshop}@postgres:5432/${POSTGRES_DB:-workshop}?serverVersion=${POSTGRES_VERSION:-17}&charset=utf8"
      MESSENGER_TRANSPORT_DSN: "redis://valkey:6379/messages"
      VALKEY_URL: ${VALKEY_URL:-redis://valkey:6379}
      MEILI_URL: ${MEILI_URL:-http://meilisearch:7700}
      MEILI_MASTER_KEY: ${MEILI_MASTER_KEY:-workshop-meili-key}
      MAILER_DSN: ${MAILER_DSN:-smtp://mailpit:1025}
      AWS_ENDPOINT: ${AWS_ENDPOINT:-http://localstack:4566}
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID:-workshop}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY:-workshop}
      AWS_DEFAULT_REGION: ${AWS_DEFAULT_REGION:-us-east-1}
      S3_BUCKET: ${S3_BUCKET:-workshop-uploads}
      TEMPORAL_ADDRESS: ${TEMPORAL_ADDRESS:-temporal:7233}
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-lgtm:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-workshop-app}
      OTEL_PHP_AUTOLOAD_ENABLED: ${OTEL_PHP_AUTOLOAD_ENABLED:-true}
      XDEBUG_MODE: ${XDEBUG_MODE:-off}
      FRANKENPHP_WORKER_CONFIG: watch
    volumes:
      - ./:/app
      - caddy-data:/data
      - caddy-config:/config
    networks:
      - workshop
    depends_on:
      postgres:
        condition: service_healthy
      valkey:
        condition: service_healthy
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app.rule=Host(`app.workshop.localhost`)"
      - "traefik.http.routers.app.entrypoints=websecure"
      - "traefik.http.routers.app.tls=true"
      - "traefik.http.services.app.loadbalancer.server.port=8080"
    develop:
      watch:
        - action: sync
          path: ./src
          target: /app/src
          ignore:
            - "*.test.php"
        - action: sync
          path: ./templates
          target: /app/templates
        - action: sync
          path: ./config
          target: /app/config
        - action: rebuild
          path: ./composer.json
        - action: rebuild
          path: ./composer.lock

  worker-messenger:
    build:
      context: .
      dockerfile: docker/frankenphp/Dockerfile
      target: dev
    restart: unless-stopped
    command: ["php", "bin/console", "messenger:consume", "async", "--time-limit=3600", "-vv"]
    environment:
      APP_ENV: ${APP_ENV:-dev}
      DATABASE_URL: "postgresql://${POSTGRES_USER:-workshop}:${POSTGRES_PASSWORD:-workshop}@postgres:5432/${POSTGRES_DB:-workshop}?serverVersion=${POSTGRES_VERSION:-17}&charset=utf8"
      MESSENGER_TRANSPORT_DSN: "redis://valkey:6379/messages"
      VALKEY_URL: ${VALKEY_URL:-redis://valkey:6379}
      MAILER_DSN: ${MAILER_DSN:-smtp://mailpit:1025}
      AWS_ENDPOINT: ${AWS_ENDPOINT:-http://localstack:4566}
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID:-workshop}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY:-workshop}
      S3_BUCKET: ${S3_BUCKET:-workshop-uploads}
      MEILI_URL: ${MEILI_URL:-http://meilisearch:7700}
      MEILI_MASTER_KEY: ${MEILI_MASTER_KEY:-workshop-meili-key}
      TEMPORAL_ADDRESS: ${TEMPORAL_ADDRESS:-temporal:7233}
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-lgtm:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_SERVICE_NAME: workshop-messenger-worker
    volumes:
      - ./:/app
    networks:
      - workshop
    depends_on:
      postgres:
        condition: service_healthy
      valkey:
        condition: service_healthy

volumes:
  caddy-data:
  caddy-config:

networks:
  workshop:
    external: true
```

- [ ] **Step 5: Commit**

```bash
git add compose/app.yml compose/database.yml compose/cache.yml docker/postgres/
git commit -m "Add compose modules: app (FrankenPHP), database (PostgreSQL), cache (Valkey)"
```

---

### Task 5: Compose modules — search, mail, storage

**Files:**
- Create: `compose/search.yml`
- Create: `compose/mail.yml`
- Create: `compose/storage.yml`
- Create: `docker/localstack/init-s3.sh`

- [ ] **Step 1: Create `compose/search.yml`**

```yaml
services:
  meilisearch:
    image: getmeili/meilisearch:v1
    restart: unless-stopped
    environment:
      MEILI_MASTER_KEY: ${MEILI_MASTER_KEY:-workshop-meili-key}
      MEILI_ENV: development
    volumes:
      - meilisearch-data:/meili_data
    networks:
      - workshop
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:7700/health"]
      interval: 10s
      timeout: 5s
      retries: 5
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.meilisearch.rule=Host(`search.workshop.localhost`)"
      - "traefik.http.routers.meilisearch.entrypoints=websecure"
      - "traefik.http.routers.meilisearch.tls=true"
      - "traefik.http.services.meilisearch.loadbalancer.server.port=7700"

volumes:
  meilisearch-data:

networks:
  workshop:
    external: true
```

- [ ] **Step 2: Create `compose/mail.yml`**

```yaml
services:
  mailpit:
    image: axllent/mailpit
    restart: unless-stopped
    networks:
      - workshop
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8025/livez"]
      interval: 10s
      timeout: 5s
      retries: 5
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.mailpit.rule=Host(`mailpit.workshop.localhost`)"
      - "traefik.http.routers.mailpit.entrypoints=websecure"
      - "traefik.http.routers.mailpit.tls=true"
      - "traefik.http.services.mailpit.loadbalancer.server.port=8025"

networks:
  workshop:
    external: true
```

- [ ] **Step 3: Create `docker/localstack/init-s3.sh`**

```bash
#!/bin/bash
echo "Creating S3 bucket..."
awslocal s3 mb s3://workshop-uploads
echo "S3 bucket created."
```

- [ ] **Step 4: Create `compose/storage.yml`**

```yaml
services:
  localstack:
    image: localstack/localstack
    restart: unless-stopped
    environment:
      SERVICES: s3
      DEFAULT_REGION: ${AWS_DEFAULT_REGION:-us-east-1}
    volumes:
      - localstack-data:/var/lib/localstack
      - ./docker/localstack/init-s3.sh:/etc/localstack/init/ready.d/init-s3.sh:ro
    networks:
      - workshop
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:4566/_localstack/health"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  localstack-data:

networks:
  workshop:
    external: true
```

- [ ] **Step 5: Commit**

```bash
git add compose/search.yml compose/mail.yml compose/storage.yml docker/localstack/
git commit -m "Add compose modules: search (Meilisearch), mail (Mailpit), storage (LocalStack)"
```

---

### Task 6: Compose modules — temporal, observability

**Files:**
- Create: `compose/temporal.yml`
- Create: `compose/observability.yml`

- [ ] **Step 1: Create `compose/temporal.yml`**

```yaml
services:
  temporal:
    image: temporalio/auto-setup:latest
    restart: unless-stopped
    environment:
      DB: postgres12
      DB_PORT: 5432
      POSTGRES_USER: ${POSTGRES_USER:-workshop}
      POSTGRES_PWD: ${POSTGRES_PASSWORD:-workshop}
      POSTGRES_SEEDS: postgres
      DYNAMIC_CONFIG_FILE_PATH: config/dynamicconfig/development-sql.yaml
      DEFAULT_NAMESPACE: default
      DEFAULT_NAMESPACE_RETENTION: 24h
    networks:
      - workshop
    depends_on:
      postgres:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "temporal", "operator", "cluster", "health"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 30s

  temporal-ui:
    image: temporalio/ui:latest
    restart: unless-stopped
    environment:
      TEMPORAL_ADDRESS: temporal:7233
      TEMPORAL_CORS_ORIGINS: "https://app.workshop.localhost,https://temporal.workshop.localhost"
      TEMPORAL_CSRF_COOKIE_INSECURE: "true"
    networks:
      - workshop
    depends_on:
      temporal:
        condition: service_healthy
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.temporal-ui.rule=Host(`temporal.workshop.localhost`)"
      - "traefik.http.routers.temporal-ui.entrypoints=websecure"
      - "traefik.http.routers.temporal-ui.tls=true"
      - "traefik.http.services.temporal-ui.loadbalancer.server.port=8080"

  worker-temporal:
    build:
      context: .
      dockerfile: docker/frankenphp/Dockerfile
      target: dev
    restart: unless-stopped
    command: ["php", "bin/console", "temporal:worker"]
    environment:
      APP_ENV: ${APP_ENV:-dev}
      DATABASE_URL: "postgresql://${POSTGRES_USER:-workshop}:${POSTGRES_PASSWORD:-workshop}@postgres:5432/${POSTGRES_DB:-workshop}?serverVersion=${POSTGRES_VERSION:-17}&charset=utf8"
      TEMPORAL_ADDRESS: ${TEMPORAL_ADDRESS:-temporal:7233}
      MAILER_DSN: ${MAILER_DSN:-smtp://mailpit:1025}
      AWS_ENDPOINT: ${AWS_ENDPOINT:-http://localstack:4566}
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID:-workshop}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY:-workshop}
      S3_BUCKET: ${S3_BUCKET:-workshop-uploads}
      MEILI_URL: ${MEILI_URL:-http://meilisearch:7700}
      MEILI_MASTER_KEY: ${MEILI_MASTER_KEY:-workshop-meili-key}
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-lgtm:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_SERVICE_NAME: workshop-temporal-worker
    volumes:
      - ./:/app
    networks:
      - workshop
    depends_on:
      temporal:
        condition: service_healthy
      postgres:
        condition: service_healthy

networks:
  workshop:
    external: true
```

- [ ] **Step 2: Create `compose/observability.yml`**

```yaml
services:
  otel-lgtm:
    image: grafana/otel-lgtm
    restart: unless-stopped
    networks:
      - workshop
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.grafana.rule=Host(`grafana.workshop.localhost`)"
      - "traefik.http.routers.grafana.entrypoints=websecure"
      - "traefik.http.routers.grafana.tls=true"
      - "traefik.http.services.grafana.loadbalancer.server.port=3000"

networks:
  workshop:
    external: true
```

- [ ] **Step 3: Commit**

```bash
git add compose/temporal.yml compose/observability.yml
git commit -m "Add compose modules: temporal (server + UI + worker), observability (Grafana LGTM)"
```

---

### Task 7: Compose modules — tunnel, debug, test

**Files:**
- Create: `compose/tunnel.yml`
- Create: `compose/debug.yml`
- Create: `compose/test.yml`

- [ ] **Step 1: Create `compose/tunnel.yml`**

```yaml
services:
  cloudflared:
    image: cloudflare/cloudflared:latest
    restart: unless-stopped
    command: tunnel --no-autoupdate --url http://app:8080
    networks:
      - workshop
    depends_on:
      - app
    profiles:
      - tunnel

  ngrok:
    image: ngrok/ngrok:latest
    restart: unless-stopped
    command: http app:8080 --log stdout
    environment:
      NGROK_AUTHTOKEN: ${NGROK_AUTHTOKEN:-}
    networks:
      - workshop
    depends_on:
      - app
    profiles:
      - tunnel-ngrok
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.ngrok.rule=Host(`ngrok.workshop.localhost`)"
      - "traefik.http.routers.ngrok.entrypoints=websecure"
      - "traefik.http.routers.ngrok.tls=true"
      - "traefik.http.services.ngrok.loadbalancer.server.port=4040"

networks:
  workshop:
    external: true
```

- [ ] **Step 2: Create `compose/debug.yml`**

```yaml
services:
  buggregator:
    image: ghcr.io/buggregator/server:latest
    restart: unless-stopped
    networks:
      - workshop
    profiles:
      - debug
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.buggregator.rule=Host(`debug.workshop.localhost`)"
      - "traefik.http.routers.buggregator.entrypoints=websecure"
      - "traefik.http.routers.buggregator.tls=true"
      - "traefik.http.services.buggregator.loadbalancer.server.port=8000"

networks:
  workshop:
    external: true
```

- [ ] **Step 3: Create `compose/test.yml`**

```yaml
services:
  selenium:
    image: selenium/standalone-chrome:latest
    restart: unless-stopped
    networks:
      - workshop
    profiles:
      - test
    shm_size: "2g"

networks:
  workshop:
    external: true
```

- [ ] **Step 4: Commit**

```bash
git add compose/tunnel.yml compose/debug.yml compose/test.yml
git commit -m "Add compose modules: tunnel (Cloudflared/ngrok), debug (Buggregator), test (Selenium)"
```

---

### Task 8: Root compose.yml with include

**Files:**
- Create: `compose.yml`

- [ ] **Step 1: Create `compose.yml`**

```yaml
include:
  - path: compose/proxy.yml
  - path: compose/database.yml
  - path: compose/cache.yml
  - path: compose/search.yml
  - path: compose/mail.yml
  - path: compose/storage.yml
  - path: compose/temporal.yml
  - path: compose/observability.yml
  - path: compose/app.yml
  - path: compose/tunnel.yml
  - path: compose/debug.yml
  - path: compose/test.yml
```

- [ ] **Step 2: Validate compose config**

Run: `docker compose config --quiet`
Expected: no errors

- [ ] **Step 3: Commit**

```bash
git add compose.yml
git commit -m "Add root compose.yml with include for all modules"
```

---

### Task 9: Makefile

**Files:**
- Create: `Makefile`

- [ ] **Step 1: Create `Makefile`**

```makefile
.PHONY: help setup up down restart logs shell watch build \
        composer console migrate seed test \
        debug tunnel tunnel-ngrok reset lint

.DEFAULT_GOAL := help

# Colors
BLUE := \033[34m
GREEN := \033[32m
RESET := \033[0m

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "$(BLUE)%-20s$(RESET) %s\n", $$1, $$2}'

# --- Setup ---

setup: ## First-time setup: generate TLS certs, copy .env
	@echo "$(GREEN)Generating mkcert certificates...$(RESET)"
	@command -v mkcert >/dev/null 2>&1 || { echo "Error: mkcert is not installed. Install it: https://github.com/FiloSottile/mkcert"; exit 1; }
	@mkcert -install 2>/dev/null || true
	@mkcert -cert-file docker/traefik/certs/local-cert.pem \
		-key-file docker/traefik/certs/local-key.pem \
		"workshop.localhost" "*.workshop.localhost"
	@if [ ! -f .env.local ]; then \
		cp .env .env.local; \
		echo "$(GREEN)Created .env.local from .env$(RESET)"; \
	fi
	@echo "$(GREEN)Setup complete! Run 'make up' to start.$(RESET)"

build: ## Build Docker images
	docker compose build

# --- Docker ---

up: ## Start all services
	docker compose up -d --wait

down: ## Stop all services
	docker compose down

restart: down up ## Restart all services

logs: ## Follow logs (all services)
	docker compose logs -f

shell: ## Open bash in app container
	docker compose exec app bash

watch: ## Start with file watching
	docker compose watch

# --- App ---

composer: ## Run composer command (usage: make composer ARGS="require foo/bar")
	docker compose exec app composer $(ARGS)

console: ## Run Symfony console (usage: make console ARGS="cache:clear")
	docker compose exec app php bin/console $(ARGS)

migrate: ## Run database migrations
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

seed: ## Seed database with test data
	docker compose exec app php bin/console app:seed

test: ## Run PHPUnit tests
	docker compose exec app php bin/phpunit

# --- Profiles ---

debug: ## Start with debug profile (Buggregator)
	docker compose --profile debug up -d

tunnel: ## Start with tunnel profile (Cloudflare)
	docker compose --profile tunnel up -d

tunnel-ngrok: ## Start with ngrok tunnel
	docker compose --profile tunnel-ngrok up -d

# --- Maintenance ---

reset: ## Full reset: volumes, rebuild, migrate, seed
	docker compose down -v
	docker compose up -d --wait --build
	@sleep 5
	$(MAKE) migrate
	$(MAKE) seed

lint: ## Run linters (PHPStan + CS Fixer)
	docker compose exec app vendor/bin/phpstan analyse
	docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

- [ ] **Step 2: Test help output**

Run: `make help`
Expected: colored list of all targets with descriptions

- [ ] **Step 3: Commit**

```bash
git add Makefile
git commit -m "Add Makefile with all development targets"
```

---

### Task 10: Symfony skeleton install and smoke test

**Files:**
- Create: Symfony project files (via `composer create-project`)
- Verify: `make setup && make up` works end-to-end

- [ ] **Step 1: Build the Docker image**

Run: `docker compose build app`
Expected: Image builds successfully

- [ ] **Step 2: Install Symfony skeleton inside the container**

Run from host (the container must be started temporarily without depends_on checks):

```bash
docker compose run --rm --no-deps app composer create-project symfony/skeleton /tmp/symfony "7.2.*"
docker compose run --rm --no-deps app bash -c "cp -r /tmp/symfony/* /tmp/symfony/.* /app/ 2>/dev/null; rm -rf /tmp/symfony"
```

- [ ] **Step 3: Generate mkcert certificates**

Run: `make setup`
Expected: Certificates created in `docker/traefik/certs/`, `.env.local` created

- [ ] **Step 4: Start all services**

Run: `make up`
Expected: All services start, `docker compose ps` shows all healthy

- [ ] **Step 5: Smoke test — Traefik dashboard**

Open: `https://traefik.workshop.localhost`
Expected: Traefik dashboard with all routers listed

- [ ] **Step 6: Smoke test — Symfony app**

Run: `curl -k https://app.workshop.localhost`
Expected: Symfony welcome page (HTTP 200)

- [ ] **Step 7: Smoke test — service UIs**

Open these URLs and verify they load:
- `https://mailpit.workshop.localhost` — Mailpit UI
- `https://search.workshop.localhost` — Meilisearch (JSON response)
- `https://grafana.workshop.localhost` — Grafana login page
- `https://temporal.workshop.localhost` — Temporal UI

- [ ] **Step 8: Commit Symfony files**

```bash
git add -A
git commit -m "Add Symfony 7.2 skeleton, verify full stack smoke test"
```

---

## Summary

| Task | What | Depends on |
|------|------|-----------|
| 1 | Project scaffold (.env, .gitignore, .dockerignore) | — |
| 2 | Traefik + mkcert TLS | — |
| 3 | FrankenPHP Dockerfile | — |
| 4 | Compose: app, database, cache | 2, 3 |
| 5 | Compose: search, mail, storage | — |
| 6 | Compose: temporal, observability | 4 |
| 7 | Compose: tunnel, debug, test | — |
| 8 | Root compose.yml with include | 2–7 |
| 9 | Makefile | 8 |
| 10 | Symfony skeleton + smoke test | 9 |
