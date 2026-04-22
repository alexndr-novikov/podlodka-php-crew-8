# Сценарий демонстрации: Docker для PHP-разработчика в 2026

> Демо идёт после слайдовой части. Проект уже развёрнут (`make up`), все сервисы работают.
> Тайминг: ~15-20 минут.

---

## Часть 1: Обзор исходного кода (~8 мин)

Показываем в IDE/редакторе. Цель — показать, как всё из слайдов реализовано на практике.

### 1.1. compose.yml — модульная структура

**Файл:** `compose.yml`

На что обратить внимание:
- **`include:`** — каждый инфраструктурный сервис в отдельном файле
- Нет `version:` — поле больше не нужно
- Одна общая `networks: workshop` — все модули видят друг друга
- Порядок include не важен — Compose сам разрешает зависимости

```
compose.yml           ← точка входа, только include + networks
compose/proxy.yml     ← Traefik
compose/database.yml  ← PostgreSQL
compose/cache.yml     ← Valkey
compose/search.yml    ← Meilisearch
compose/mail.yml      ← Mailpit
compose/storage.yml   ← LocalStack S3 + S3 Manager UI
compose/app.yml       ← FrankenPHP + Messenger worker
compose/tunnel.yml    ← Cloudflare/ngrok (profile)
compose/debug.yml     ← Buggregator (profile)
compose/test.yml      ← Selenium (profile)
```

### 1.2. compose/app.yml — ключевой файл

**Файл:** `compose/app.yml`

Показать сверху вниз, акценты:

1. **`env_file:` с required/optional** (строки 8-12)
   - `app.env` — обязательный, в репо
   - `app.env.local` — опциональный, в .gitignore
   - Показать `compose/app.env` — всего 2 переменные
   - «Одинаковые ключи — последний файл побеждает»

2. **`environment:` с интерполяцией** (строки 13-27)
   - `${POSTGRES_USER:-workshop}` — дефолты прямо в compose
   - Все подключения к инфраструктуре по именам сервисов (postgres, valkey, meilisearch, mailpit, localstack)

3. **`depends_on` с `service_healthy`** (строки 34-38)
   - «App не стартует, пока БД и кэш не пройдут healthcheck»
   - Перейти к `compose/database.yml` — показать `pg_isready` healthcheck
   - Перейти к `compose/cache.yml` — показать `valkey-cli ping`

4. **`post_start` lifecycle hooks** (строки 39-42)
   - `chown -R www-data:www-data /app/var` — фикс прав
   - `cache:warmup` от пользователя www-data
   - «Выполняется после старта, но до того как Traefik начнёт роутить трафик»

5. **Traefik labels** (строки 43-48)
   - `Host(app.workshop.localhost)` — автоматический роутинг
   - «Traefik читает labels из Docker API — zero config»

6. **`develop.watch`** (строки 49-65)
   - `sync` для src/, templates/, config/ — мгновенные изменения
   - `rebuild` для composer.json/lock — пересборка образа
   - `ignore: ["*.test.php"]` — не синхронизировать тесты

7. **`extends` для worker** (строки 67-79)
   - «worker-messenger наследует всё от app — build, environment, volumes»
   - `command:` переопределяет CMD на `messenger:consume`
   - `labels: []` — сбрасывает Traefik (worker не нужен в proxy)
   - `develop.watch: []` — сбрасывает watch

### 1.3. compose/proxy.yml — Traefik

**Файл:** `compose/proxy.yml`

- Docker socket прокинут read-only
- Конфиг через volumes: `traefik.yml`, `dynamic.yml`, TLS-сертификаты
- `${HTTP_PORT:-80}` и `${HTTPS_PORT:-443}` — порты настраиваемые через .env

Бегло показать `docker/traefik/traefik.yml`:
- `providers.docker` — автодискавери по сети workshop
- `entryPoints.web` → redirect на websecure
- `entryPoints.websecure` → :443

### 1.4. compose/storage.yml — LocalStack + S3 Manager

**Файл:** `compose/storage.yml`

- `SERVICES: s3` — запускаем только S3
- Healthcheck: `curl -f http://localhost:4566/_localstack/health`
- Init-скрипт: `docker/localstack/init-s3.sh` → `awslocal s3 mb s3://workshop-uploads`
- **s3manager** — UI для просмотра бакетов, зависит от localstack healthy

### 1.5. compose/tunnel.yml — profiles

**Файл:** `compose/tunnel.yml`

- `profiles: [tunnel]` — не запускается по умолчанию
- `docker compose --profile tunnel up -d` или `make tunnel`
- Cloudflare: бесплатный, без регистрации, без interstitial
- ngrok: требует NGROK_AUTHTOKEN

### 1.6. Dockerfile — multi-stage

**Файл:** `docker/frankenphp/Dockerfile`

Показать 3 стадии:

1. **base** (строки 3-50)
   - `dunglas/frankenphp:1-php8.4-bookworm` — FrankenPHP на Debian
   - Heredoc `RUN <<-EOF` для apt-get
   - `install-php-extensions` — mlocati installer
   - `COPY --link` — копирование без инвалидации слоёв
   - `COPY --chmod=755` — права без отдельного RUN
   - `HEALTHCHECK` — встроенный health через Caddy metrics
   - `ENTRYPOINT` → docker-entrypoint.sh

2. **dev** (строки 52-70)
   - Добавляет xdebug, pcov
   - `php.ini-development`
   - `--watch` флаг в CMD
   - Комментарий про non-root: «dev работает от root — FrankenPHP требует»

3. **prod** (строки 72-95)
   - `php.ini-production`
   - `COPY --link composer.json composer.lock` → отдельный слой
   - `--mount=type=cache,target=/root/.composer/cache` — кэш между сборками
   - `composer install --no-dev` → `dump-autoload --classmap-authoritative`
   - `USER www-data` — non-root в production

### 1.7. docker-entrypoint.sh — умный запуск

**Файл:** `docker/frankenphp/docker-entrypoint.sh`

- Автоматический `composer install` если нет vendor/
- Ожидание БД через PDO (30 секунд timeout)
- Автомиграции в dev-режиме
- «Всё что нужно для первого запуска — `make up` и готово»

### 1.8. Makefile — developer experience

**Файл:** `Makefile`

- `make help` — автогенерация справки
- `make setup` — mkcert сертификаты + .env.local
- `make up` → `docker compose up -d --wait`
- `make watch` — hot-reload
- `make tunnel` — Cloudflare tunnel через profile
- `make lint` — PHPStan + CS Fixer в контейнере

---

## Часть 2: Терминал — запуск и CLI (~3 мин)

### 2.1. docker compose config

```bash
docker compose config --services
```

Показать список всех сервисов. Обратить внимание, что tunnel/debug/selenium не в списке — они за profiles.

### 2.2. docker compose ps

```bash
docker compose ps
```

Показать запущенные сервисы, их порты, статус healthcheck (healthy).

### 2.3. docker compose stats (если время есть)

```bash
docker compose stats
```

Показать потребление ресурсов каждым сервисом в реальном времени. Ctrl+C для выхода.

---

## Часть 3: Браузер — демонстрация работы (~7 мин)

Все URL через HTTPS с доверенными сертификатами (mkcert).

### 3.1. Dashboard

**URL:** `https://app.workshop.localhost:8443`

- Главная страница с карточками: Email, Storage, Search, Cache, Webhooks, Onboarding
- Каждая карточка ведёт к демо конкретного Docker-сервиса
- «Одно приложение — 6 интеграций с инфраструктурой, всё через Docker»

### 3.2. Email → Mailpit

1. Перейти в **Email** на дашборде
2. Заполнить форму, отправить email
3. Открыть **Mailpit UI**: `https://mailpit.workshop.localhost:8443`
4. Показать полученное письмо, HTML-preview
5. «SMTP на порту 1025, UI на 8025, через Traefik — красивый URL»

### 3.3. Storage → S3 (LocalStack)

1. Перейти в **Хранилище (S3)** на дашборде
2. Загрузить файл
3. Показать его в списке, удалить
4. Открыть **S3 Manager UI**: `https://s3.workshop.localhost:8443`
5. Показать бакет `workshop-uploads`, загруженные файлы
6. «LocalStack эмулирует AWS S3. Init-скрипт создаёт бакет при старте»

### 3.4. Search → Meilisearch

1. Перейти в **Поиск** на дашборде
2. Добавить документ (имя, email)
3. Выполнить поиск — показать мгновенные результаты
4. «Meilisearch v1 — рекомендация Laravel Scout, typo-tolerance из коробки»

### 3.5. Traefik Dashboard

**URL:** `https://traefik.workshop.localhost:8443/dashboard/`

- Показать список роутеров: app, mailpit, s3manager, meilisearch
- Показать entrypoints: web (redirect), websecure (TLS)
- «Вся маршрутизация через Docker labels — ничего не настраиваем руками»

### 3.6. Onboarding — сквозной сценарий (если время есть)

1. Перейти в **Onboarding** на дашборде
2. Зарегистрировать пользователя
3. «Один запрос → БД + async email (Messenger → Valkey → Worker) + S3 аватар + Meilisearch индекс»
4. Проверить: письмо в Mailpit, файл в S3 Manager, документ в поиске

---

## Часть 4: Tunnel — бонус (~2 мин, если есть время)

```bash
make tunnel
```

- Показать URL от Cloudflare в логах
- Открыть URL с телефона / дать ссылку аудитории
- «Бесплатно, без регистрации, удобно для тестирования вебхуков»

---

## Чеклист перед выступлением

- [ ] `make up` — все сервисы запущены
- [ ] `make seed` — тестовые данные в БД и Meilisearch
- [ ] `https://app.workshop.localhost:8443` — открывается
- [ ] `https://mailpit.workshop.localhost:8443` — открывается
- [ ] `https://s3.workshop.localhost:8443` — открывается
- [ ] `https://traefik.workshop.localhost:8443/dashboard/` — открывается
- [ ] IDE открыта на compose.yml
- [ ] Терминал в директории проекта
- [ ] Слайды на слайде 12 (Структура демо) перед началом демо-части
