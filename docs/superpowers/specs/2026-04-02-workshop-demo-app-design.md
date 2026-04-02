# Workshop Demo App — Design Spec

> Docker для PHP-разработчика: готовое демо-приложение для воркшопа Podlodka PHP Crew 8.
> Участники клонируют репозиторий и запускают всё одной командой.

---

## 1. Общие решения

- **Symfony 7.2** (стабильный релиз)
- **FrankenPHP** (dunglas/frankenphp, Bookworm) как application server
- **Traefik v3** как единый entry point с TLS termination (mkcert)
- **Подход Monolith Compose** — один `compose.yml` с `include:` модульных файлов из `compose/`
- Всё стартует одной командой: `make up` → `docker compose up -d --wait`
- UI: Twig + Tailwind CSS + Stimulus + Turbo (AssetMapper, без Node.js)
- API: все эндпоинты доступны как JSON, Swagger через NelmioApiDocBundle

---

## 2. Docker-инфраструктура

### 2.1. Сетевая топология

```
Браузер / curl
     |
     v
  Traefik :443/:80  (TLS termination, mkcert)
     |
     |-- app.workshop.localhost      -> FrankenPHP :8080
     |-- mailpit.workshop.localhost  -> Mailpit :8025
     |-- search.workshop.localhost   -> Meilisearch :7700
     |-- grafana.workshop.localhost  -> Grafana :3000
     |-- temporal.workshop.localhost -> Temporal UI :8233
     |-- traefik.workshop.localhost  -> Traefik Dashboard
     +-- debug.workshop.localhost    -> Buggregator :8000 (profile: debug)
```

- `*.workshop.localhost` резолвится в 127.0.0.1 без правки /etc/hosts
- TLS: mkcert сертификаты, генерируются при `make setup`
- Одна Docker-сеть `workshop` (bridge)

### 2.2. Compose-структура (include)

```
compose.yml                  <- entry point
compose/app.yml              <- FrankenPHP + workers
compose/database.yml         <- PostgreSQL
compose/cache.yml            <- Valkey
compose/search.yml           <- Meilisearch
compose/mail.yml             <- Mailpit
compose/storage.yml          <- LocalStack (S3)
compose/temporal.yml         <- Temporal server + UI
compose/observability.yml    <- Grafana LGTM (OTel Collector + Loki + Tempo + Mimir + Grafana)
compose/proxy.yml            <- Traefik + mkcert certs
compose/tunnel.yml           <- Cloudflared / ngrok (profile: tunnel / tunnel-ngrok)
compose/debug.yml            <- Buggregator (profile: debug)
compose/test.yml             <- Selenium (profile: test)
```

### 2.3. Profiles

| Profile | Сервисы | Когда |
|---------|---------|-------|
| (default) | app, postgres, valkey, meilisearch, mailpit, localstack, temporal-server, temporal-ui, otel-lgtm, traefik, worker-temporal, worker-messenger, scheduler | Всегда |
| `debug` | buggregator | Для отладки VarDumper/Xdebug |
| `tunnel` | cloudflared | Для тестирования вебхуков извне |
| `tunnel-ngrok` | ngrok | Альтернативный тунель |
| `test` | selenium | Для браузерных тестов |

### 2.4. Named Volumes

| Volume | Назначение |
|--------|-----------|
| `postgres-data` | Данные PostgreSQL |
| `valkey-data` | Данные Valkey |
| `meilisearch-data` | Индексы Meilisearch |
| `temporal-data` | Данные Temporal |
| `localstack-data` | Данные LocalStack |
| `caddy-data` | Данные Caddy (FrankenPHP) |
| `caddy-config` | Конфигурация Caddy |

### 2.5. Точка входа

```bash
make setup   # Первый раз: генерация mkcert сертификатов, создание .env.local
make up      # docker compose up -d --wait
make down    # docker compose down
make logs    # docker compose logs -f
make shell   # docker compose exec app bash
```

---

## 3. Dockerfile (FrankenPHP)

### 3.1. Базовый образ

`dunglas/frankenphp:latest-php8.4-bookworm`

### 3.2. Multi-stage

```
base (общие зависимости)
  |-- dev  (Xdebug, pcov, Composer, Symfony CLI)
  +-- prod (OPcache preload, --no-dev, worker mode)
```

Для воркшопа: target `dev`.

### 3.3. PHP-расширения

**Base:** pdo_pgsql, redis, intl, gd, zip, opcache, amqp, sockets, imagick, bcmath, opentelemetry, grpc, protobuf

**Dev:** xdebug, pcov

Установка через `docker-php-extension-installer` (mlocati).

### 3.4. Контейнеры на одном образе

| Сервис | Команда | Назначение |
|--------|---------|-----------|
| `app` | FrankenPHP (default entrypoint) | Веб-сервер с worker mode |
| `worker-temporal` | `php bin/console temporal:worker` | Temporal PHP worker |
| `worker-messenger` | `php bin/console messenger:consume async` | Symfony Messenger consumer |
| `scheduler` | `php bin/console messenger:consume scheduler_default` | Symfony Scheduler |

### 3.5. Caddyfile

FrankenPHP слушает на :8080 по HTTP внутри Docker-сети. TLS терминируется на Traefik.

---

## 4. Сервисы инфраструктуры

### 4.1. PostgreSQL

- Образ: `postgres:17`
- Healthcheck: `pg_isready`
- Init-скрипт: создание БД `workshop` и `temporal`
- Volume: `postgres-data`

### 4.2. Valkey

- Образ: `valkey/valkey:8`
- Healthcheck: `valkey-cli ping`
- Назначение: Symfony Cache, Messenger transport (async), sessions

### 4.3. Meilisearch

- Образ: `getmeili/meilisearch:v1`
- Healthcheck: `curl -f http://localhost:7700/health`
- Traefik: `search.workshop.localhost`
- Master key через .env

### 4.4. Mailpit

- Образ: `axllent/mailpit`
- SMTP: :1025, UI: :8025
- Healthcheck: `curl -f http://localhost:8025/livez`
- Traefik: `mailpit.workshop.localhost`

### 4.5. LocalStack

- Образ: `localstack/localstack`
- Сервисы: S3 (+ SQS опционально)
- Init-скрипт: создание bucket `workshop-uploads`
- Symfony Flysystem -> S3 adapter -> `http://localstack:4566`

### 4.6. Temporal

- `temporalio/auto-setup` — сервер (PostgreSQL как storage, БД `temporal`)
- `temporalio/ui` — веб-интерфейс
- Healthcheck: `tctl cluster health`
- Traefik: `temporal.workshop.localhost`

### 4.7. Buggregator (profile: debug)

- Образ: `ghcr.io/buggregator/server`
- Порты: 8000 (UI), 9912 (VarDumper), 9913 (Monolog)
- Traefik: `debug.workshop.localhost`

---

## 5. Observability

### 5.1. Grafana LGTM

- Образ: `grafana/otel-lgtm`
- Единый контейнер: OTel Collector + Loki + Grafana + Tempo + Mimir
- OTLP gRPC: :4317, OTLP HTTP: :4318
- Grafana: :3000 -> Traefik `grafana.workshop.localhost`
- Datasources предконфигурированы

### 5.2. PHP OpenTelemetry

Пакеты: `open-telemetry/sdk`, `open-telemetry/exporter-otlp`, `open-telemetry/auto-symfony`

Env variables:
```
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-lgtm:4318
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
OTEL_SERVICE_NAME=workshop-app
OTEL_PHP_AUTOLOAD_ENABLED=true
```

### 5.3. Что видно в Grafana

- **Traces**: HTTP -> Controller -> Doctrine -> Redis -> External HTTP
- **Logs**: Monolog -> Loki, коррелированные с трейсами по trace_id
- **Metrics**: PHP runtime, HTTP request duration/count
- Готовый дашборд через provisioning

---

## 6. Webhook Tunnel

### 6.1. Cloudflare Tunnel (по умолчанию, profile: tunnel)

- Образ: `cloudflare/cloudflared`
- Команда: `cloudflared tunnel --no-autoupdate --url http://app:8080`
- Бесплатно, без регистрации
- Генерирует временный публичный URL

### 6.2. ngrok (альтернатива, profile: tunnel-ngrok)

- Образ: `ngrok/ngrok`
- Требует auth token в .env.local
- Web inspector на :4040

### 6.3. Демо-сценарий

```
Внешний запрос -> Cloudflare Tunnel -> FrankenPHP -> WebhookController:
  1. Валидация подписи
  2. Логирование в OpenTelemetry (span: "webhook.received")
  3. Dispatch Symfony Messenger message
  4. Response 200
  -> Messenger Worker обрабатывает событие
  -> Всё видно в Grafana как единый trace
```

Эндпоинты:
- `POST /webhook/stripe` — приём webhook с валидацией
- `POST /webhook/test` — простой echo + log
- `GET /webhook` — UI: кнопка "Отправить тестовый webhook" (roundtrip через публичный URL)

---

## 7. Symfony-приложение

### 7.1. Структура

```
src/
  Controller/
    DashboardController.php      # Главная: карточки-плитки на все демо
    MailController.php           # Отправка email -> Mailpit
    StorageController.php        # Upload/download -> LocalStack S3
    SearchController.php         # Поиск -> Meilisearch
    CacheController.php          # Valkey get/set/benchmark
    WorkflowController.php       # Запуск Temporal workflows
    WebhookController.php        # Приём вебхуков
    ObservabilityController.php  # Генерация трейсов/ошибок
    OnboardingController.php     # Сквозной сценарий
  Entity/
    User.php                     # Единственная сущность
  Message/
    SendWelcomeEmail.php
    ProcessImageUpload.php
    HandleWebhookPayload.php
  MessageHandler/
    SendWelcomeEmailHandler.php
    ProcessImageUploadHandler.php
    HandleWebhookPayloadHandler.php
  Temporal/
    Workflow/
      OnboardingWorkflow.php          # Таймеры, ретраи, multi-step
      ImageProcessingWorkflow.php     # Параллельные activity
    Activity/
      EmailActivity.php
      StorageActivity.php
      SearchActivity.php
```

### 7.2. Изолированные эндпоинты

| Эндпоинт | Сервис | Что демонстрирует |
|----------|--------|-------------------|
| `GET /mail` | Mailpit | Форма + отправка email, ссылка на Mailpit UI |
| `GET /storage` | LocalStack S3 | Upload файла, листинг bucket |
| `GET /search` | Meilisearch | Автокомплит, CRUD записей |
| `GET /cache` | Valkey | Set/get/delete, TTL, benchmark |
| `GET /workflow` | Temporal | Запуск workflows, просмотр статуса |
| `GET /webhook` | Cloudflared | Отправить тест, просмотр логов |
| `GET /observability` | OTel + Grafana | Генерация трейса, ошибки, метрики |

Каждая страница: форма (Tailwind + Stimulus), результат, ссылка на UI сервиса.

### 7.3. Сквозной сценарий: User Onboarding

```
GET /onboarding -> Форма регистрации (имя, email, аватар)

POST /onboarding ->
  1. User -> PostgreSQL (Doctrine)
  2. Аватар -> S3 (Flysystem -> LocalStack)
  3. Профиль -> Meilisearch
  4. Temporal OnboardingWorkflow:
     a. SendWelcomeEmail -> Mailpit
     b. Sleep(30s)
     c. SendFollowUpEmail
     d. CheckProfileComplete
  5. Dispatch Messenger message (async, Valkey)
  6. Redirect -> /onboarding/{id}/status

GET /onboarding/{id}/status ->
  - Реалтайм UI: данные из БД + статус Temporal workflow
  - Turbo Stream обновления
  - Ссылки: Mailpit, Grafana trace, Temporal UI
```

### 7.4. Temporal Workflows

**OnboardingWorkflow:**
Start -> SendWelcomeEmail -> Sleep(30s) -> SendFollowUpEmail -> CheckProfileComplete -> End
- Retry policy: 3 attempts, exponential backoff
- Каждый шаг виден в Temporal UI

**ImageProcessingWorkflow:**
Start -> parallel: [ResizeThumbnail, ResizeMedium, ResizeLarge] -> UploadAllToS3 -> UpdateDatabase -> End
- Fan-out/fan-in паттерн

### 7.5. API + Swagger

- NelmioApiDocBundle
- Content negotiation: HTML (Twig) / JSON (Accept header)
- Swagger UI: `app.workshop.localhost/api/doc`

### 7.6. UI Stack

- AssetMapper (без Node.js)
- Tailwind CSS (Symfonycasts Tailwind bundle, CLI binary)
- Stimulus контроллеры (автокомплит, live-обновление)
- Turbo (SPA-like навигация)
- Dashboard: карточки-плитки с иконками для каждого демо

---

## 8. Файлы конфигурации

```
/
  compose.yml
  compose/
    app.yml
    database.yml
    cache.yml
    search.yml
    mail.yml
    storage.yml
    temporal.yml
    observability.yml
    proxy.yml
    tunnel.yml
    debug.yml
    test.yml
  docker/
    frankenphp/
      Dockerfile
      Caddyfile
      conf.d/         # PHP ini overrides
    traefik/
      traefik.yml     # Static configuration
      certs/           # mkcert certificates (gitignored)
    localstack/
      init-s3.sh      # Create bucket on startup
    postgres/
      init.sql         # Create databases
    grafana/
      dashboards/      # Pre-built dashboards
  Makefile
  .env
  .env.local           # gitignored, личные настройки
  .dockerignore
```

---

## 9. Makefile targets

```makefile
setup          # Генерация mkcert сертификатов, копирование .env -> .env.local
up             # docker compose up -d --wait
down           # docker compose down
restart        # down + up
logs           # docker compose logs -f
shell          # docker compose exec app bash
watch          # docker compose watch

# Приложение
composer       # docker compose exec app composer $(ARGS)
console        # docker compose exec app php bin/console $(ARGS)
migrate        # console doctrine:migrations:migrate --no-interaction
seed           # console app:seed (заполнить тестовыми данными)
test           # docker compose exec app php bin/phpunit

# Profiles
debug          # docker compose --profile debug up -d
tunnel         # docker compose --profile tunnel up -d
tunnel-ngrok   # docker compose --profile tunnel-ngrok up -d

# Утилиты
reset          # down -v + up + migrate + seed
lint           # phpstan + cs-fixer
```
