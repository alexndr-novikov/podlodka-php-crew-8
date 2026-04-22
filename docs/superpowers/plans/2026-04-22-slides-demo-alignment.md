# Выравнивание демо-проекта со слайдами доклада

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Привести демо-проект в соответствие со слайдами — убрать то, что не будет в докладе, добавить то, что обещано на слайдах.

**Architecture:** 12 задач, разбитых на 3 блока: удаление лишнего (observability, amqp), приведение compose/Dockerfile к заявленным паттернам (extends, lifecycle hooks, env_file, multi-stage, non-root, mount cache), исправление мелких багов (.env, Makefile).

**Tech Stack:** Docker Compose, Dockerfile (FrankenPHP/Bookworm), Symfony 7.2, PHP 8.4

---

## Блок A: Убрать лишнее

### Task 1: Удалить Observability из compose и приложения

Observability (07-observability.md) не включён в slides.md и не будет показан. Нужно убрать все следы: compose-модуль, env-переменные, контроллер, шаблон, карточку на дашборде.

**Files:**
- Modify: `compose.yml:8` — убрать строку `- path: compose/observability.yml`
- Modify: `compose/app.yml:23-27` — убрать `OTEL_*` переменные из `app`
- Modify: `compose/app.yml:84-86` — убрать `OTEL_*` переменные из `worker-messenger`
- Modify: `src/Controller/DashboardController.php:55-62` — убрать карточку Observability
- Delete: `src/Controller/ObservabilityController.php`
- Delete: `templates/observability/index.html.twig`

**НЕ удалять:** файл `compose/observability.yml` и `slides/07-observability.md` — оставляем на случай, если понадобится вернуть.

- [ ] **Step 1: Убрать include observability из compose.yml**

В `compose.yml` удалить строку:
```yaml
  - path: compose/observability.yml
```

- [ ] **Step 2: Убрать OTEL_* переменные из app сервиса в compose/app.yml**

Удалить из секции `environment` сервиса `app` (строки 23-27):
```yaml
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-lgtm:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-workshop-app}
      OTEL_PHP_AUTOLOAD_ENABLED: ${OTEL_PHP_AUTOLOAD_ENABLED:-true}
```

- [ ] **Step 3: Убрать OTEL_* переменные из worker-messenger в compose/app.yml**

Удалить из секции `environment` сервиса `worker-messenger` (строки 84-86):
```yaml
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-lgtm:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_SERVICE_NAME: workshop-messenger-worker
```

- [ ] **Step 4: Убрать карточку Observability из DashboardController**

В `src/Controller/DashboardController.php` удалить массив:
```php
            [
                'title' => 'Observability',
                'description' => 'Трейсы, логи, метрики через OpenTelemetry',
                'route' => 'observability_index',
                'icon' => "\u{1F4CA}",
                'service' => 'Grafana',
                'serviceUrl' => 'https://grafana.workshop.localhost:8443',
            ],
```

- [ ] **Step 5: Удалить ObservabilityController и шаблон**

```bash
rm src/Controller/ObservabilityController.php
rm templates/observability/index.html.twig
rmdir templates/observability
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Remove observability: not included in the talk"
```

---

### Task 2: Удалить расширение amqp из Dockerfile

В проекте нет RabbitMQ, Messenger использует Valkey. Расширение `amqp` — лишний вес.

**Files:**
- Modify: `docker/frankenphp/Dockerfile:37-39` — убрать `amqp`

- [ ] **Step 1: Убрать amqp из Dockerfile**

Заменить блок:
```dockerfile
# PHP extensions — additional (heavier builds)
RUN install-php-extensions \
    amqp \
    imagick
```

На:
```dockerfile
# PHP extensions — additional (heavier builds)
RUN install-php-extensions \
    imagick
```

- [ ] **Step 2: Commit**

```bash
git add docker/frankenphp/Dockerfile
git commit -m "Remove amqp extension: no RabbitMQ in demo stack"
```

---

## Блок B: Привести к заявленным паттернам

### Task 3: Переписать worker-messenger на extends

Слайд 20 (01-evolution.md) показывает паттерн `extends`. В `compose/app.yml` есть комментарий `# rewrite to extend`. Нужно реализовать.

**Files:**
- Modify: `compose/app.yml` — заменить `worker-messenger` на extends от `app`

- [ ] **Step 1: Переписать worker-messenger**

Заменить весь сервис `worker-messenger` (строки 64-95) на:

```yaml
  worker-messenger:
    extends:
      service: app
    restart: unless-stopped
    command: ["php", "bin/console", "messenger:consume", "async", "--time-limit=3600", "-vv"]
    labels: []
    depends_on:
      postgres:
        condition: service_healthy
      valkey:
        condition: service_healthy
    develop:
      watch: []
```

Ключевые моменты:
- `extends: { service: app }` наследует build, environment, volumes, networks
- `labels: []` — сбросить traefik-лейблы, worker не должен быть виден через reverse proxy
- `develop: { watch: [] }` — сбросить watch, worker не нужно синхронизировать (он использует bind mount)
- `command` переопределяет CMD
- `depends_on` переопределяем, убирая лишнее

- [ ] **Step 2: Проверить валидность**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker compose config --quiet
```

Expected: нет ошибок (exit code 0).

- [ ] **Step 3: Commit**

```bash
git add compose/app.yml
git commit -m "Refactor worker-messenger to use extends from app service"
```

---

### Task 4: Добавить lifecycle hooks (post_start)

Слайд 15 (01-evolution.md) показывает `post_start` с `chown` и `cache:warmup`. Добавим в app сервис.

**Files:**
- Modify: `compose/app.yml` — добавить `post_start` в сервис `app`

- [ ] **Step 1: Добавить post_start в app**

В сервис `app`, после блока `depends_on` и перед `labels`, добавить:

```yaml
    post_start:
      - command: chown -R www-data:www-data /app/var
      - command: php bin/console cache:warmup
        user: www-data
```

- [ ] **Step 2: Проверить валидность**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker compose config --quiet
```

- [ ] **Step 3: Commit**

```bash
git add compose/app.yml
git commit -m "Add post_start lifecycle hooks to app service"
```

---

### Task 5: Добавить env_file паттерн

Слайд 13 (01-evolution.md) показывает `env_file: [required, optional]`. Добавим в app как дополнительную демонстрацию.

**Files:**
- Create: `compose/app.env` — общие env-переменные app-сервиса
- Modify: `compose/app.yml` — добавить `env_file` в сервис `app`

- [ ] **Step 1: Создать compose/app.env**

```env
APP_ENV=dev
APP_SECRET=workshop-secret-change-me
```

- [ ] **Step 2: Добавить env_file в app сервис**

В начало сервиса `app`, перед `environment:`, добавить:

```yaml
    env_file:
      - path: ./app.env
        required: true
      - path: ./app.env.local
        required: false
```

Удалить из `environment:` дублирующиеся ключи `APP_ENV` и `APP_SECRET`, так как они теперь в `app.env`.

- [ ] **Step 3: Добавить compose/app.env.local в .gitignore**

Добавить строку в `.gitignore`:
```
compose/app.env.local
```

- [ ] **Step 4: Проверить валидность**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker compose config --quiet
```

- [ ] **Step 5: Commit**

```bash
git add compose/app.yml compose/app.env .gitignore
git commit -m "Add env_file pattern with required/optional to app service"
```

---

### Task 6: Добавить production target в Dockerfile

Слайд 11 (11-bestpractices.md) говорит: «Multi-stage builds: dev → test → production». Сейчас есть только `base → dev`.

**Files:**
- Modify: `docker/frankenphp/Dockerfile` — добавить `prod` target после `dev`

- [ ] **Step 1: Добавить prod stage**

В конец Dockerfile добавить:

```dockerfile
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
```

Ключевое: этот stage демонстрирует `--mount=type=cache`, `COPY --link`, `USER www-data` (non-root), оптимизацию autoload — всё из слайдов.

- [ ] **Step 2: Проверить что prod target собирается (dry run)**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker build --target prod --file docker/frankenphp/Dockerfile --dry-run . 2>&1 | head -5
```

Если `--dry-run` не поддерживается, просто проверим синтаксис:
```bash
docker build --target prod --file docker/frankenphp/Dockerfile --check .
```

- [ ] **Step 3: Commit**

```bash
git add docker/frankenphp/Dockerfile
git commit -m "Add prod target to Dockerfile with mount cache, non-root user"
```

---

### Task 7: Добавить --mount=type=cache в dev stage

В dev-режиме composer install происходит в entrypoint. Но мы можем показать паттерн в Dockerfile тоже — добавив установку dev-зависимостей в отдельном шаге с cache mount.

**Files:**
- Modify: `docker/frankenphp/Dockerfile` — добавить mount cache для composer в dev stage

- [ ] **Step 1: Добавить cache mount в dev stage**

В dev stage, после установки xdebug/pcov и перед `COPY --link`, добавить:

```dockerfile
# Pre-install dependencies with cache mount (speeds up rebuild)
COPY --link composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-scripts --prefer-dist --no-progress
```

- [ ] **Step 2: Проверить валидность Dockerfile**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker build --target dev --file docker/frankenphp/Dockerfile --check . 2>&1
```

- [ ] **Step 3: Commit**

```bash
git add docker/frankenphp/Dockerfile
git commit -m "Add --mount=type=cache for composer in dev stage"
```

---

### Task 8: Добавить non-root user в dev (опционально через ENV)

Слайд 11: «Non-root user в контейнерах». Prod target уже будет с `USER www-data` (Task 6). Для dev — FrankenPHP требует root для binding. Добавим комментарий-пояснение в dev stage.

**Files:**
- Modify: `docker/frankenphp/Dockerfile` — добавить пояснительный комментарий в dev stage

- [ ] **Step 1: Добавить комментарий в dev stage**

После `ENV FRANKENPHP_WORKER_CONFIG=watch`, добавить:

```dockerfile
# Note: dev runs as root because FrankenPHP needs it for auto-HTTPS/binding
# Production stage uses USER www-data (see prod target)
```

Этого достаточно — non-root реализован в prod target (Task 6), а в dev это ожидаемо и объяснимо.

- [ ] **Step 2: Commit**

```bash
git add docker/frankenphp/Dockerfile
git commit -m "Document non-root user strategy in Dockerfile"
```

---

## Блок C: Исправить .env и Makefile

### Task 9: Исправить .env — привести к Docker-реальности

`.env` содержит `DATABASE_URL` с `127.0.0.1` и user `app`, а также `MESSENGER_TRANSPORT_DSN=doctrine://default`. Это стандартные Symfony-генерированные значения, которые не совпадают с Docker-сетапом.

**Files:**
- Modify: `.env:39` — исправить DATABASE_URL
- Modify: `.env:46` — исправить MESSENGER_TRANSPORT_DSN

- [ ] **Step 1: Исправить DATABASE_URL**

Заменить:
```
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

На:
```
DATABASE_URL="postgresql://workshop:workshop@postgres:5432/workshop?serverVersion=17&charset=utf8"
```

- [ ] **Step 2: Исправить MESSENGER_TRANSPORT_DSN**

Заменить:
```
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

На:
```
MESSENGER_TRANSPORT_DSN=redis://valkey:6379/messages
```

- [ ] **Step 3: Добавить недостающие env-переменные**

Добавить в конец `.env` (перед последней секцией mailer или после неё) переменные, используемые в compose, чтобы .env был полной справкой:

```env
# Meilisearch
MEILI_URL=http://meilisearch:7700
MEILI_MASTER_KEY=workshop-meili-key

# S3 (LocalStack)
AWS_ENDPOINT=http://localstack:4566
AWS_ACCESS_KEY_ID=workshop
AWS_SECRET_ACCESS_KEY=workshop
AWS_DEFAULT_REGION=us-east-1
S3_BUCKET=workshop-uploads

# Valkey
VALKEY_URL=redis://valkey:6379
```

- [ ] **Step 4: Commit**

```bash
git add .env
git commit -m "Fix .env to match Docker compose environment"
```

---

### Task 10: Исправить slides-share в Makefile

В `Makefile:107` serve запускается на порту 3031, но cloudflared tunnel указывает на 3030.

**Files:**
- Modify: `Makefile:107`

- [ ] **Step 1: Исправить порт**

Заменить:
```makefile
	@npx --yes serve slides/dist -l 3031 &>/dev/null & sleep 2 && cloudflared tunnel --url http://localhost:3030
```

На:
```makefile
	@npx --yes serve slides/dist -l 3031 &>/dev/null & sleep 2 && cloudflared tunnel --url http://localhost:3031
```

- [ ] **Step 2: Commit**

```bash
git add Makefile
git commit -m "Fix slides-share: tunnel port matches serve port"
```

---

### Task 11: Удалить закомментированные OTel расширения из Dockerfile

Раз observability убрана из доклада, закомментированный блок с `grpc`, `protobuf`, `opentelemetry` — мусор.

**Files:**
- Modify: `docker/frankenphp/Dockerfile:41-45`

- [ ] **Step 1: Удалить закомментированный блок**

Удалить:
```dockerfile
# PHP extensions — observability (uncomment when needed, grpc takes ~10min to compile)
# RUN install-php-extensions \
#     grpc \
#     protobuf \
#     opentelemetry
```

- [ ] **Step 2: Commit**

```bash
git add docker/frankenphp/Dockerfile
git commit -m "Remove commented-out OTel extensions from Dockerfile"
```

---

### Task 12: Финальная проверка

**Files:** Нет новых изменений — только валидация.

- [ ] **Step 1: Проверить compose config**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker compose config --quiet
```

Expected: exit code 0, нет ошибок.

- [ ] **Step 2: Проверить что все маршруты приложения работают**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker compose exec app php bin/console debug:router | grep -E 'dashboard|mail|storage|search|cache|webhook|onboarding'
```

Expected: 7 маршрутов (без observability).

- [ ] **Step 3: Проверить что карточек на дашборде 6 (было 7)**

```bash
grep -c "'title'" src/Controller/DashboardController.php
```

Expected: `6`

- [ ] **Step 4: Проверить Dockerfile синтаксис**

```bash
cd /Users/alexndrnovikov/apps/podlodka-php-crew-8 && docker build --target dev --file docker/frankenphp/Dockerfile --check . 2>&1
```

---

## Порядок выполнения

Tasks 1-2 (удаление) → Tasks 3-8 (паттерны) → Tasks 9-11 (фиксы) → Task 12 (валидация).

Tasks 1 и 2 независимы друг от друга. Tasks 6, 7, 8, 11 все изменяют Dockerfile — выполнять строго последовательно. Tasks 9 и 10 независимы.
