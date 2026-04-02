# План воркшопа: Docker для PHP-разработчика в 2026 году

> Фокус: локальное окружение, актуальные практики, современные контейнеры.

---

## 1. Эволюция Docker Compose

### 1.1. Формат и CLI
- `compose.yml` вместо `docker-compose.yml` (новое каноничное имя)
- `docker compose` (встроенная команда) вместо `docker-compose` (отдельный бинарник на Python, deprecated)
- Compose V2 — Go-реализация, плагин Docker CLI
- `docker compose watch` — встроенный file-watching с автоматическим sync/rebuild
- `docker compose up --wait` — ожидание healthcheck всех сервисов перед выходом
- `docker compose alpha dry-run` — предпросмотр без запуска
- `docker compose events` — стриминг событий для отладки

### 1.2. Новый синтаксис и возможности compose.yml
- `develop.watch` — нативная конфигурация hot-reload (sync, rebuild, sync+restart, restart)
- `include` — подключение внешних compose-файлов (замена COMPOSE_FILE)
- `configs` и `secrets` на уровне top-level (без Swarm)
- Поддержка `profiles` для группировки сервисов (dev, test, debug)
- `depends_on` с `condition: service_healthy` и `restart: true`
- Интерполяция переменных: `${VAR:-default}`, `${VAR:?error}`
- `annotations` — метаданные для сервисов
- Удаление `version:` — поле больше не нужно и игнорируется
- `additional_contexts` в build-секции — проброс дополнительных build contexts
- `cgroup` — настройка cgroup namespace per-service

### 1.3. `develop.watch` — нюансы и подводные камни

**watch vs bind mount — ключевое ограничение:**
- Compose watch **игнорирует пути, совпадающие с bind mount volumes** — выводит предупреждение `"path '...' also declared by a bind mount volume, this path won't be monitored!"`
- Причина: одновременный sync + bind mount на одном пути может вызвать бесконечный цикл синхронизации
- Исключение: действие `rebuild` разрешено вместе с bind mount (не вызывает цикл) — исправлено в [docker/compose#12089](https://github.com/docker/compose/pull/12089)
- Действие `restart` (без sync) добавлено отдельно для работы с bind mount — [docker/compose#12375](https://github.com/docker/compose/pull/12375). Позволяет перезапускать контейнер при изменении файлов без синхронизации (полезно для Laravel queue workers, Symfony messenger consumers)

**Когда использовать bind mount, а когда watch sync:**
- **Bind mount** — для локальной разработки на Linux (практически нет overhead), для больших проектов (тысячи файлов), когда нужна двусторонняя синхронизация (генерируемые файлы, vendor)
- **Watch sync** — для macOS/Windows (быстрее, чем bind mount через виртуализацию), для целенаправленной синхронизации конкретных путей, для одностороннего потока (хост → контейнер)
- **Watch rebuild** — для файлов, изменение которых требует пересборки образа (composer.json, package.json, Dockerfile)
- **Watch restart** — для bind mount + перезапуск контейнера при изменении конфигов (e.g. `.env`, `.rr.yaml`, `supervisord.conf`)

**Различия поведения на разных ОС:**

  **macOS:**
  - Используется `fsevents` API для отслеживания изменений файлов — эффективный, нативный
  - **Регрессия в Compose v5.0.1 (Docker Desktop 4.57)**: бинарник был скомпилирован без build-тега `fsevents`, fallback-watcher открывал file descriptor на каждую поддиректорию (включая `vendor/`, `node_modules/`), что приводило к краху с ошибкой `too many open files` — исправлено в [docker/compose#13532](https://github.com/docker/compose/pull/13532)
  - Рекомендация: добавлять `ignore:` секцию в watch для `vendor/`, `node_modules/`, `.git/`, `var/` — снижает нагрузку даже при нативном fsevents
  - Bind mount на macOS идёт через виртуализацию (VirtioFS / gRPC FUSE) → watch sync может быть быстрее для частых мелких изменений

  **Linux:**
  - Используется `inotify` — эффективный, но есть системный лимит на количество watchers
  - `fs.inotify.max_user_watches` по умолчанию 8192–65536 (зависит от дистрибутива) — может не хватить для крупных проектов
  - Лимит **не изолирован по контейнерам** — все контейнеры + хост делят один пул inotify watches
  - Решение: `sysctl fs.inotify.max_user_watches=524288` на хосте
  - Bind mount на Linux работает нативно без overhead → часто предпочтительнее watch sync

  **Windows (WSL2):**
  - Файлы должны лежать в файловой системе WSL2 (`/home/...`), а не в Windows (`/mnt/c/...`) — иначе inotify-события не приходят
  - CIFS (Windows FS → Linux) не поддерживает inotify — watch не будет срабатывать на изменения из Windows-редактора, если проект на `/mnt/c/`
  - Рекомендация: хранить код в WSL2 FS, использовать VS Code Remote WSL

**Общие рекомендации:**
- Всегда заполняйте `ignore:` — исключайте `vendor/`, `node_modules/`, `.git/`, `var/cache/`, `storage/`
- Для PHP-проектов `sync` подходит для исходного кода (`src/`, `app/`, `templates/`), `rebuild` — для `composer.json` + `composer.lock`
- Проверяйте поведение через `docker compose events` — показывает, какие файлы триггерят actions
- При проблемах: `docker compose watch --no-build` для отладки без пересборки

### 1.4. Compose environments & .env
- `.env` файл: приоритет, множественные файлы (`env_file: [.env, .env.local]`)
- `COMPOSE_PROJECT_NAME` и `COMPOSE_PROFILES` в .env
- `COMPOSE_FILE` с несколькими файлами для overlay-конфигурации
- Переход от `environment:` к `env_file:` для чистоты конфигурации

---

## 2. Современный Dockerfile для PHP

### 2.1. Базовые образы
- Официальные PHP-образы: `php:8.3-fpm`, `php:8.4-fpm`, `php:8.4-cli`
- Фиксация версий: `php:8.4.2-fpm-bookworm` вместо `php:8.4-fpm`
- `FROM --platform=$BUILDPLATFORM` для multi-arch

### 2.2. Alpine vs Debian (bookworm) — подводные камни миграции
- **Размер**: Alpine ~50 MB vs Debian ~250 MB, но после установки расширений разница сокращается
- **musl vs glibc** — корень большинства проблем:

  **Imagick / ImageMagick:**
  - Alpine использует `imagemagick` собранный с musl — другие дефолтные delegates
  - Отсутствие или неполная поддержка шрифтов: нет `fontconfig` и `ttf-*` пакетов из коробки — текст на изображениях рендерится с fallback-шрифтами или не рендерится вовсе
  - Различия в цветовых профилях: Alpine-сборка может не включать ICC-профили, что приводит к отличиям в цветопередаче (особенно CMYK → RGB)
  - `heif`/`avif` delegate может отсутствовать в Alpine-пакете — форматы HEIC/AVIF не конвертируются
  - Разное поведение при обработке PDF (Ghostscript-версия и policy.xml отличаются)
  - Решение: явно доустанавливать `fontconfig`, `ttf-freefont`, `ghostscript`, `libheif-dev` и пересобирать или использовать `docker-php-extension-installer`

  **GD:**
  - Alpine: `libpng`, `libjpeg-turbo`, `freetype` нужно ставить вручную (`apk add`) перед сборкой расширения
  - Различия в рендеринге шрифтов из-за musl — визуально другой anti-aliasing

  **DNS-резолвинг:**
  - musl не поддерживает `search` и `ndots` в `/etc/resolv.conf` так же как glibc
  - В Docker-сетях это может приводить к неожиданным ошибкам при резолве имён сервисов (особенно с точками в имени)
  - musl DNS-резолвер не использует `/etc/nsswitch.conf` — порядок lookup отличается

  **Локали и iconv:**
  - Alpine не поддерживает glibc-локали (`locale-gen` нет) — `setlocale()` работает только с `C` и `POSIX`
  - `iconv()` в musl — урезанная реализация, может некорректно обрабатывать некоторые кодировки (особенно `CP1251`, `KOI8-R`, актуально для русскоязычных проектов)
  - Решение: установка `gnu-libiconv` и переопределение `LD_PRELOAD`

  **Производительность:**
  - musl `malloc` медленнее glibc `malloc` под высокой нагрузкой и при частых аллокациях (PHP-FPM с большим количеством воркеров)
  - Решение: можно использовать `jemalloc` как аллокатор

  **Совместимость расширений:**
  - Некоторые PECL-расширения не компилируются на musl без патчей (grpc, protobuf бывали проблемными)
  - Бинарные расширения, собранные под glibc, не работают на Alpine

  **Рекомендация:** для production PHP-приложений предпочтительнее Debian (bookworm) — стабильнее, меньше edge-кейсов. Alpine оправдан для микросервисов без тяжёлых расширений или для уменьшения размера финального образа в multi-stage (финальный слой — Alpine CLI без расширений)

### 2.3. Актуальные BuildKit-фичи
- `RUN --mount=type=cache` — кэш Composer, apt, npm между сборками
- `RUN --mount=type=secret` — безопасная передача токенов (Composer auth, SSH keys)
- `RUN --mount=type=ssh` — проброс SSH-агента для приватных репозиториев
- `RUN --mount=type=bind` — bind файлов с хоста без COPY
- `COPY --link` — копирование без инвалидации предыдущих слоёв
- `COPY --chmod` и `COPY --chown` без отдельного RUN
- Heredoc в Dockerfile (`RUN <<EOF ... EOF`) — многострочные скрипты
- `ADD --checksum` — верификация скачиваемых файлов
- Multi-stage builds с `--target` для dev/prod/test

### 2.4. Установка PHP-расширений
- `docker-php-extension-installer` (mlocati) — де-факто стандарт
- Список типичных расширений: pdo_pgsql, pdo_mysql, redis, intl, gd, zip, opcache, xdebug, pcov, amqp, sockets, excimer
- Разделение dev/prod расширений через multi-stage

### 2.5. Безопасность
- Запуск от не-root пользователя
- Синхронизация UID/GID через build args
- `USER` инструкция в Dockerfile
- Минимизация attack surface: удаление dev-пакетов в production

---

## 3. Современные PHP-рантаймы

### 3.1. FrankenPHP
- Встроенный application server на Go (на базе Caddy)
- Worker mode — PHP-процессы переживают запросы (аналог RoadRunner)
- Нативная поддержка HTTP/2, HTTP/3
- Автоматический HTTPS через Let's Encrypt / локальные сертификаты
- Early Hints (103)
- Официальный образ `dunglas/frankenphp`
- Интеграция с Symfony (symfony/runtime) и Laravel (Octane)
- Mercure (real-time) и Vulcain (preloading) из коробки

### 3.2. Caddy как reverse proxy
- Замена Nginx / Traefik для локальной разработки
- Автоматический HTTPS для локальных доменов
- Caddyfile vs JSON-конфигурация
- Связка: Caddy → PHP-FPM или Caddy через FrankenPHP

### 3.3. RoadRunner
- Application server на Go с PHP-воркерами
- gRPC, WebSocket, Centrifuge, Jobs (очереди), KV, Metrics
- Образ `ghcr.io/roadrunner-server/roadrunner`
- Интеграция со Spiral Framework, Laravel (Octane), Symfony

### 3.4. Сравнение подходов
- Классический стек: Nginx + PHP-FPM
- FrankenPHP (Caddy + embedded PHP)
- RoadRunner (Go app server + PHP workers)
- Swoole / OpenSwoole (async PHP-расширение)
- Когда что использовать, плюсы и минусы для локальной разработки

---

## 4. Контейнеры для инфраструктуры

### 4.1. Базы данных
- **PostgreSQL** — `postgres:17` (замена MySQL как default в Laravel 11+)
- **MySQL** — `mysql:8.4` / `mysql:9.0`
- **MariaDB** — `mariadb:11`
- **MongoDB** — `mongo:8`
- **Healthcheck** для каждой БД (pg_isready, mysqladmin ping)
- Named volumes для персистентности данных
- Инициализация через `/docker-entrypoint-initdb.d/`

### 4.2. Кэш и очереди
- **Redis** — `redis:7` с `redis-stack` для RedisInsight/RedisSearch/RedisJSON
- **Valkey** — `valkey/valkey:8` (fork Redis после смены лицензии, drop-in замена)
- **KeyDB** — `eqalpha/keydb` (многопоточный Redis-совместимый)
- **DragonflyDB** — `docker.dragonflydb.io/dragonflydb/dragonfly` (высокопроизводительная замена Redis)
- **RabbitMQ** — `rabbitmq:4-management`
- **Apache Kafka** — `bitnami/kafka` (KRaft mode без ZooKeeper) / `apache/kafka`
- **NATS** — `nats:2` — легковесная альтернатива для pub/sub и очередей

### 4.3. Поиск
- **Meilisearch** — `getmeili/meilisearch:v1` (рекомендация Laravel Scout)
- **Typesense** — `typesense/typesense:27` (альтернатива)
- **Elasticsearch** — `elasticsearch:8` (для сложных случаев)
- **OpenSearch** — `opensearchproject/opensearch:2` (open-source fork Elasticsearch)
- **Manticore Search** — `manticoresearch/manticore` (лёгкая альтернатива, бывший Sphinx)

### 4.4. Почта
- **Mailpit** — `axllent/mailpit` (замена MailHog, который unmaintained)
  - Современный UI, поддержка HTML-preview
  - SMTP на порту 1025, веб-интерфейс на 8025
  - Поддержка POP3
  - API для интеграционных тестов
- Почему не MailHog: проект заброшен с 2022, не обновляется

### 4.5. Объектное хранилище (S3)
- **LocalStack** — `localstack/localstack` (S3, SQS, SNS, DynamoDB, Lambda и др.)
  - Эмуляция полной экосистемы AWS
  - Community edition бесплатен
- **MinIO** — `minio/minio` (по-прежнему хорош для чистого S3)
- Когда LocalStack vs MinIO: если нужен только S3 — MinIO проще; если нужны SQS/SNS/Lambda — LocalStack

### 4.6. Reverse proxy и маршрутизация
- **Caddy** — `caddy:2` (автоматический HTTPS, простая конфигурация)
- **Traefik** — `traefik:v3` (автоматический service discovery по Docker labels)
- **Nginx Proxy Manager** — `jc21/nginx-proxy-manager` (UI для управления)
- Локальные домены: `*.localhost`, `*.test` + mkcert для доверенного HTTPS

---

## 5. Контейнеры для разработки и отладки

### 5.1. Xdebug 3
- Настройка через переменные окружения (`XDEBUG_MODE`, `XDEBUG_CONFIG`)
- Modes: debug, profile, trace, coverage
- `xdebug.client_host=host.docker.internal`
- Включение/выключение через profiles или отдельный Dockerfile target

### 5.2. Профилирование и мониторинг
- **Excimer** — PHP-расширение для low-overhead profiling (используется Wikipedia)
- **SPX** — простой профилировщик с веб-UI
- **Buggregator** — `ghcr.io/buggregator/server` (all-in-one debug server для PHP)
  - Сборщик Xdebug, VarDumper (Symfony), Ray, SMTP, Sentry, Inspector, Profiler
  - Trap — CLI-альтернатива
- **Grafana + Prometheus** — мониторинг (если нужен)

### 5.3. Инструменты качества кода (в контейнерах)
- PHPStan / Psalm — статический анализ
- PHP CS Fixer / PHP_CodeSniffer — code style
- Rector — автоматический рефакторинг
- Запуск через `docker compose run` или как отдельные сервисы

---

## 6. Workflow-движки и фоновые задачи

### 6.1. Temporal
- `temporalio/auto-setup` — сервер с автоматической настройкой
- `temporalio/ui` — веб-интерфейс
- PHP SDK (`temporal/sdk`) — workflow и activity в PHP
- Связка: Temporal server + PHP worker в отдельном контейнере
- Альтернативы: Symfony Messenger, Laravel Horizon

### 6.2. Supervisor / Process Manager
- `supervisord` внутри контейнера для worker-процессов (queue consumers, schedulers)
- Альтернатива: отдельные контейнеры для каждого воркера (предпочтительнее)
- Laravel: `php artisan queue:work` как отдельный сервис
- Symfony: `messenger:consume` как отдельный сервис

---

## 7. Observability Stack (локально)

### 7.1. OpenTelemetry
- PHP SDK (`open-telemetry/sdk`, авто-инструментирование)
- OTel Collector — `otel/opentelemetry-collector-contrib`
- Traces, Metrics, Logs — единый стандарт

### 7.2. Трейсинг
- **Jaeger** — `jaegertracing/all-in-one` (визуализация трейсов)
- **Zipkin** — `openzipkin/zipkin` (альтернатива)
- **Grafana Tempo** — `grafana/tempo` (хранение трейсов для Grafana)

### 7.3. Логирование
- **Grafana Loki** — `grafana/loki` (агрегация логов, label-based)
- Docker logging driver → Loki
- **Grafana** — `grafana/grafana` (единый UI для логов, метрик, трейсов)

### 7.4. All-in-one
- **Grafana LGTM Stack** — `grafana/otel-lgtm` (Loki + Grafana + Tempo + Mimir в одном контейнере для локальной разработки)

---

## 8. CI/CD и тестирование

### 8.1. Генерация PDF
- **Gotenberg** — `gotenberg/gotenberg:8` (Chromium + LibreOffice, REST API)
- Альтернатива wkhtmltopdf (deprecated)

### 8.2. Браузерное тестирование
- **Selenium** — `selenium/standalone-chrome`
- **Playwright** — `mcr.microsoft.com/playwright`
- **Browserless** — `browserless/chrome` (headless Chrome API)
- Laravel Dusk / Symfony Panther интеграция

### 8.3. Аудит и безопасность образов
- **Hadolint** — линтер Dockerfile
- **Dockle** — аудит безопасности образов
- **Trivy** — сканер уязвимостей (CVE)
- **Docker Scout** — встроенный в Docker Desktop анализ (SBOM, CVE)
- **Grype** + **Syft** — open-source альтернатива от Anchore

---

## 9. Docker Desktop и альтернативы (2026)

- **Docker Desktop** — встроенный Compose, Scout, Extensions, Resource Saver
- **OrbStack** — лёгкая альтернатива для macOS (быстрее, меньше ресурсов)
- **Podman Desktop** — open-source альтернатива (rootless, daemonless)
- **Colima** — CLI-ориентированный Docker runtime для macOS/Linux
- Файловая синтаксис: VirtioFS (Docker Desktop), virtiofs (OrbStack) — решение проблемы медленных volume-mount на macOS

---

## 10. Референсные реализации из экосистемы

### Laravel
- **Laravel Sail** — официальное Docker-окружение (compose.yml с MySQL/PostgreSQL/Redis/Meilisearch/Mailpit/Selenium)
- **Laravel Octane** — FrankenPHP / RoadRunner / Swoole в production-like режиме
- **Laravel Herd** — нативная локальная среда (без Docker, для сравнения)
- Переход на PostgreSQL как default DB в Laravel 11

### Symfony
- **Symfony Docker** (dunglas/symfony-docker) — официальный стек на FrankenPHP
- **Symfony CLI** — встроенный локальный сервер (без Docker, для сравнения)
- **Symfony Messenger** — очереди через RabbitMQ/Redis/Doctrine
- **Symfony Runtime** — интеграция с FrankenPHP/RoadRunner

### Другие
- **DDEV** — универсальное Docker-окружение для PHP (Drupal, WordPress, Laravel, Symfony)
- **Lando** — ещё один Docker-based dev environment

---

## 11. Best Practices 2026

- Один процесс — один контейнер
- Healthcheck для каждого сервиса
- Named volumes для данных, bind mounts для кода
- `.dockerignore` — обязательно
- Multi-stage builds: dev → test → production
- `develop.watch` вместо сторонних file-watchers
- `profiles` для опциональных сервисов (xdebug, mailpit, adminer)
- Compose `include` для модульности
- Кэширование зависимостей через `--mount=type=cache`
- Pinning версий образов до минорной версии
- Non-root user в контейнерах
- `docker compose up --wait` в скриптах и CI
- `.env` + `.env.local` для разделения конфигурации
- Makefile / Taskfile / Just для алиасов команд

---

## 12. Структура воркшопа (предложение)

1. **Before & After** — показать `docker-compose.yml` образца 2021 и `compose.yml` образца 2026
2. **Live coding** — собрать окружение с нуля: PHP 8.4 + PostgreSQL + Redis/Valkey + Mailpit + Meilisearch
3. **Dockerfile** — написать production-ready Dockerfile с BuildKit фичами
4. **Runtime showdown** — PHP-FPM vs FrankenPHP vs RoadRunner (запуск одного приложения)
5. **Observability** — подключить OpenTelemetry + Grafana LGTM stack
6. **Temporal** — показать workflow engine для фоновых задач
7. **Q&A / Discussion**
