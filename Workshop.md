# Docker для PHP-разработчика: что изменилось за 3 года

> Воркшоп ~70 минут. Формат: слайды + live demo.

---

## Часть 1. Было → Стало (10 мин)

### Слайд: О чём этот воркшоп
- Целевая аудитория: PHP-разработчики, которые используют Docker для локальной разработки
- Что покроем: всё, что изменилось в экосистеме Docker с 2022–2023 года
- Формат: минимум теории, максимум практики

### Слайд: Мир Docker в 2022
- `docker-compose` — отдельный бинарник на Python
- `docker-compose.yml` с обязательным `version: "3.8"`
- Nginx + PHP-FPM — единственный мейнстрим-стек
- MailHog, MinIO, Elasticsearch — стандартный набор
- Xdebug включён всегда или через отдельный образ
- Volume mounts на macOS — боль

### Слайд: Мир Docker в 2026
- `docker compose` — встроенный плагин (Go)
- `compose.yml` без `version:`
- FrankenPHP / RoadRunner как альтернативы PHP-FPM
- Mailpit, LocalStack, Meilisearch, Valkey — новый набор
- `watch`, `profiles`, `include`, `healthcheck` из коробки
- OrbStack / VirtioFS — volume mounts больше не боль

### Демо: Before & After
- Показать реальный `docker-compose.yml` образца 2022 (устаревшие паттерны)
- Показать эквивалентный `compose.yml` образца 2026
- Подсветить все отличия

---

## Часть 2. Современный compose.yml (15 мин)

### Слайд: Что изменилось в Compose
- `docker compose` вместо `docker-compose`
- Файл называется `compose.yml`
- Поле `version:` убрано — игнорируется
- Compose V2 — полная Go-реализация

### Слайд: `include` — модульность
- Подключение внешних compose-файлов
- Замена `COMPOSE_FILE=a.yml:b.yml`
- Каждый файл — самодостаточный модуль (БД, кэш, мониторинг)

### Демо: include в действии
- Базовый `compose.yml` с PHP-приложением
- `compose/database.yml`, `compose/cache.yml`, `compose/mail.yml`
- Подключение через `include:`

### Слайд: `profiles` — группы сервисов
- Сервисы с `profiles: [debug]` не стартуют по умолчанию
- `docker compose --profile debug up`
- Сценарии: debug-инструменты, тестовые БД, мониторинг

### Демо: profiles
- Xdebug, Mailpit, Buggregator — в профиле `debug`
- Selenium — в профиле `test`
- Показать `COMPOSE_PROFILES=debug` в `.env`

### Слайд: `develop.watch` — нативный hot-reload
- Три действия: `sync`, `rebuild`, `sync+restart`
- Замена сторонних file-watchers и bind-mount хаков
- `docker compose watch` — встроенная команда

### Демо: watch
- Изменить PHP-файл — автоматический sync в контейнер
- Изменить `composer.json` — автоматический rebuild

### Слайд: Healthcheck и depends_on
- `depends_on` + `condition: service_healthy` — дождаться готовности
- `docker compose up --wait` — выход только после healthcheck всех сервисов
- Healthcheck для PostgreSQL: `pg_isready`
- Healthcheck для Redis: `redis-cli ping`

### Слайд: .env и переменные
- Множественные env-файлы: `env_file: [.env, .env.local]`
- Интерполяция: `${DB_PORT:-5432}`, `${DB_PASSWORD:?обязательная переменная}`
- `COMPOSE_PROFILES` и `COMPOSE_PROJECT_NAME` в `.env`
- `.env.local` в `.gitignore` — персональные настройки

---

## Часть 3. Dockerfile для PHP в 2026 (15 мин)

### Слайд: Выбор базового образа
- `php:8.4-fpm-bookworm` — Debian, стабильный выбор для production
- `php:8.4-fpm-alpine` — меньше, но есть подводные камни
- Всегда фиксируйте минорную версию

### Слайд: Alpine — подводные камни
- musl vs glibc — корень проблем
- **Imagick**: нет шрифтов, нет ICC-профилей, HEIC/AVIF не работает
- **iconv**: урезанная реализация, CP1251/KOI8-R ломается
- **DNS**: musl-резолвер не поддерживает `search`/`ndots`
- **Локали**: только `C` и `POSIX`
- **malloc**: медленнее под нагрузкой

### Слайд: Рекомендация по базовому образу
- Production: Debian (bookworm) — стабильнее, меньше сюрпризов
- Лёгкий CLI-утилиты: Alpine допустим
- Multi-stage: сборка на Debian, финальный образ — тоже Debian

### Слайд: BuildKit — что появилось
- `RUN --mount=type=cache` — кэш Composer/apt между сборками
- `RUN --mount=type=secret` — безопасные токены
- `COPY --link` — не инвалидирует предыдущие слои
- `COPY --chmod` — права без отдельного `RUN chmod`
- Heredoc: `RUN <<EOF` — многострочные скрипты

### Демо: Dockerfile с нуля
- Multi-stage: `base` → `dev` → `prod`
- `docker-php-extension-installer` для расширений
- `--mount=type=cache` для Composer
- `--mount=type=secret` для приватных пакетов
- Non-root user
- Healthcheck

### Слайд: Установка расширений
- `docker-php-extension-installer` (mlocati) — де-факто стандарт
- Одна команда вместо 10 строк apt-get + docker-php-ext-configure
- Типичный набор: pdo_pgsql, redis, intl, gd, zip, opcache, amqp, sockets
- Dev-only: xdebug, pcov — через отдельный stage/target

---

## Часть 4. PHP-рантаймы — не только PHP-FPM (10 мин)

### Слайд: Три подхода
| | Nginx + PHP-FPM | FrankenPHP | RoadRunner |
|---|---|---|---|
| Архитектура | Reverse proxy + FastCGI | Caddy + embedded PHP | Go server + PHP workers |
| Worker mode | Нет | Да | Да |
| HTTPS | Ручная настройка | Автоматический | Ручная настройка |
| HTTP/3 | Нет | Да | Нет |
| Конфигурация | nginx.conf + php-fpm.conf | Caddyfile | .rr.yaml |
| Laravel | Стандарт | Octane | Octane |
| Symfony | Стандарт | symfony-docker | Через Runtime |

### Слайд: FrankenPHP
- Один бинарник — и веб-сервер, и PHP
- Worker mode: PHP живёт между запросами (как в Go/Node)
- Автоматический HTTPS (включая localhost)
- Официальный стек Symfony (`dunglas/symfony-docker`)
- Early Hints (103), Mercure, Vulcain

### Демо: FrankenPHP
- Запустить то же приложение на FrankenPHP вместо Nginx + PHP-FPM
- Показать worker mode
- Показать автоматический HTTPS на localhost

### Слайд: RoadRunner
- Go-сервер, PHP-воркеры живут между запросами
- Больше чем HTTP: gRPC, WebSocket, очереди, KV, метрики
- `ghcr.io/roadrunner-server/roadrunner`
- Spiral Framework, Laravel Octane, Symfony Runtime

### Слайд: Когда что выбрать
- **Nginx + PHP-FPM**: legacy-проект, максимальная совместимость, привычно команде
- **FrankenPHP**: новый проект, нужен HTTPS/HTTP3, Symfony-экосистема
- **RoadRunner**: высокая нагрузка, gRPC, WebSocket, микросервисы

---

## Часть 5. Современные контейнеры для инфраструктуры (10 мин)

### Слайд: Что заменить в 2026

| Было (2022) | Стало (2026) | Почему |
|---|---|---|
| MailHog | **Mailpit** | MailHog заброшен с 2022, Mailpit — активная разработка, лучше UI |
| MinIO | **MinIO** или **LocalStack** | MinIO по-прежнему ок для S3; LocalStack если нужны SQS/SNS/Lambda |
| Elasticsearch | **Meilisearch** / **Manticore** | Легче, быстрее для типовых задач; ES для сложных кейсов |
| Redis | **Valkey** | Fork Redis после смены лицензии, drop-in замена, активная community |
| Nginx (proxy) | **Caddy** / **Traefik v3** | Автоматический HTTPS, проще конфигурация |
| wkhtmltopdf | **Gotenberg** | wkhtmltopdf deprecated, Gotenberg — Chromium + REST API |

### Слайд: Кэш и очереди — новые игроки
- **Valkey** — fork Redis (Linux Foundation), полная совместимость
- **DragonflyDB** — заявлен как 25x быстрее Redis, совместимый API
- **Kafka без ZooKeeper** — KRaft mode, `apache/kafka` образ
- **NATS** — легковесный pub/sub, альтернатива RabbitMQ для простых задач

### Слайд: Поиск
- **Meilisearch** — рекомендация Laravel Scout, мгновенный поиск, typo-tolerance
- **Typesense** — альтернатива, тоже быстрый и простой
- **Manticore Search** — наследник Sphinx, SQL-синтаксис, легковесный
- **OpenSearch** — если нужен полноценный Elasticsearch без лицензионных ограничений

### Демо: Mailpit + Valkey + Meilisearch
- Добавить в compose.yml
- Показать UI Mailpit
- Отправить тестовое письмо из PHP
- Показать поиск через Meilisearch

---

## Часть 6. Отладка и Observability (10 мин)

### Слайд: Xdebug 3 в Docker — правильно
- Через `profiles: [debug]` — не грузит в обычном режиме
- `XDEBUG_MODE=debug` через переменную окружения
- `host.docker.internal` — без танцев с IP
- Альтернатива: pcov для coverage (быстрее)

### Слайд: Buggregator — всё в одном
- Xdebug, VarDumper (Symfony), Ray, SMTP, Sentry, Profiler
- Один контейнер заменяет несколько debug-инструментов
- Trap — CLI-версия для быстрой отладки
- `ghcr.io/buggregator/server`

### Слайд: OpenTelemetry для локальной разработки
- Зачем: видеть трейсы запросов через все сервисы (PHP → PostgreSQL → Redis)
- PHP SDK + авто-инструментирование
- OTel Collector → Grafana LGTM

### Демо: Grafana LGTM Stack
- `grafana/otel-lgtm` — один контейнер: Loki + Grafana + Tempo + Mimir
- Подключить PHP OTel SDK
- Сделать запрос — увидеть трейс в Grafana
- Показать логи через Loki

---

## Часть 7. Бонус: Temporal и воркеры (5 мин)

### Слайд: Фоновые задачи — эволюция
- Cron в контейнере → отдельный контейнер с scheduler
- Supervisor → отдельные контейнеры для каждого воркера
- Очереди (RabbitMQ/Redis) → Temporal для сложных workflow

### Слайд: Temporal
- Workflow engine — оркестрация длительных бизнес-процессов
- Автоматические ретраи, таймауты, версионирование
- PHP SDK (`temporal/sdk`)
- Docker: `temporalio/auto-setup` + `temporalio/ui`
- Показать compose-конфигурацию

### Слайд: Воркеры в compose.yml
- Один сервис = один процесс
- Laravel: отдельный контейнер для `queue:work`, `schedule:run`
- Symfony: отдельный контейнер для `messenger:consume`
- Тот же образ, другая команда

---

## Часть 8. Best Practices и итоги (5 мин)

### Слайд: Чеклист для вашего проекта
- [ ] `compose.yml` без `version:`, с `include` и `profiles`
- [ ] Healthcheck для каждого сервиса + `depends_on: condition: service_healthy`
- [ ] `develop.watch` для hot-reload
- [ ] Dockerfile с multi-stage и `--mount=type=cache`
- [ ] Non-root user в контейнерах
- [ ] Фиксация версий образов до минорной
- [ ] `.dockerignore` актуален
- [ ] `.env` + `.env.local` для конфигурации
- [ ] Заменить MailHog → Mailpit, Redis → Valkey
- [ ] Попробовать FrankenPHP или RoadRunner
- [ ] Makefile / Taskfile / Just для команд

### Слайд: Референсные реализации
- **Laravel Sail** — `laravel new` из коробки генерирует compose.yml
- **Symfony Docker** — `github.com/dunglas/symfony-docker` (FrankenPHP-стек)
- **DDEV** — универсальное окружение (Drupal, WordPress, Laravel, Symfony)

### Слайд: Docker Desktop и альтернативы
- **OrbStack** — быстрее и легче на macOS
- **Podman Desktop** — open-source, rootless
- **Colima** — CLI-ориентированный, бесплатный
- VirtioFS — решение проблемы медленных volumes на macOS

### Слайд: Ресурсы
- Репозиторий воркшопа с готовыми примерами
- Ссылки на документацию: Compose Spec, FrankenPHP, RoadRunner
- Ссылки на образы: Mailpit, Valkey, Meilisearch, Buggregator, Grafana LGTM

---

## Q&A (оставшееся время)
