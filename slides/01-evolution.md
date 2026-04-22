---
layout: section
sectionNumber: '01'
---

# Эволюция Docker Compose

---

<div class="flex flex-col items-center">
  <div class="flex items-center gap-6" style="flex-wrap: nowrap;">
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.4rem; font-weight: 700; color: #b0b0b0; white-space: nowrap;">docker-compose.yml</div>
    <div style="font-size: 2.4rem; color: var(--purple);">→</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.4rem; font-weight: 700; color: var(--purple); white-space: nowrap;">compose.yml</div>
  </div>
  <div class="mt-8 text-xl" style="color: var(--text-secondary);">Новое каноничное имя файла. docker-compose.yml всё ещё работает, но deprecated.</div>
</div>

---

<div class="flex flex-col items-center">
  <div class="flex items-center gap-6" style="flex-wrap: nowrap;">
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.4rem; font-weight: 700; color: #b0b0b0; white-space: nowrap;">docker-compose up</div>
    <div style="font-size: 2.4rem; color: var(--purple);">→</div>
    <div style="font-family: 'JetBrains Mono', monospace; font-size: 2.4rem; font-weight: 700; color: var(--purple); white-space: nowrap;">docker compose up</div>
  </div>
  <div class="mt-8 text-xl text-center" style="color: var(--text-secondary);">V1 (Python) → V2 (Go). Отдельный бинарник → плагин Docker CLI. 2024: V1 окончательно удалён.</div>
</div>

---

# Формат и CLI

<div class="accent-line"></div>

- `docker compose watch` — встроенный file-watching
- `docker compose up --watch` — запуск + watch в одной команде
- `docker compose up --wait` — ожидание healthcheck
- `docker compose config` — валидация и вывод итогового YAML
- `docker compose alpha dry-run` — предпросмотр без запуска
- `docker compose stats` — ресурсы контейнеров в реальном времени

---
layout: default
class: bg-purple-50/30
---

# Новый синтаксис compose.yml

<div class="accent-line"></div>

- `develop.watch` — нативная конфигурация hot-reload
- `include` — подключение внешних compose-файлов
- `configs` и `secrets` на уровне top-level (без Swarm)
- `profiles` для группировки сервисов (dev, test, debug)
- `depends_on` с `condition: service_healthy` (и не только)
- `name:` — имя проекта прямо в файле (вместо имени директории)
- Удаление `version:` — поле больше не нужно

---
layout: code-block
---

# develop.watch — конфигурация

```yaml
services:
  php:
    develop:
      watch:
        - action: sync       # src/ → контейнер
          path: ./src
          target: /app/src
        - action: rebuild     # composer.json → пересборка
          path: ./composer.json
        - action: restart     # .env → перезапуск
          path: ./.env
      ignore:
        - vendor/
        - node_modules/
```

---
layout: compare
---

# watch vs bind mount

<div class="comparison-grid">
  <CompareCard title="Bind Mount" :items="['Linux — нет overhead', 'Большие проекты', 'Двусторонняя синхронизация', 'vendor, генерируемые файлы']" />
  <CompareCard title="Watch Sync" :items="['macOS/Windows — быстрее', 'Конкретные пути', 'Односторонний поток', 'src/, app/, templates/']" />
  <CompareCard title="Watch Rebuild" :items="['composer.json', 'package.json', 'Dockerfile']" />
  <CompareCard title="Watch Restart" :items="['.env файлы', '.rr.yaml', 'supervisord.conf']" />
</div>

---
layout: compare
alt: true
---

# watch: различия по ОС

<div class="comparison-grid">
  <CompareCard title="🍎 macOS" :items="['fsevents API — нативный', 'Регрессия в v5.0.1 (too many open files)', 'Всегда заполняйте ignore', 'VirtioFS overhead → sync может быть быстрее']" />
  <CompareCard title="🐧 Linux" :items="['inotify — лимит watchers', 'Лимит общий для всех контейнеров + хост', 'sysctl max_user_watches=524288', 'Bind mount нативно без overhead']" />
  <CompareCard title="🪟 Windows (WSL2)" :items="['Файлы в WSL2 FS, не /mnt/c/', 'CIFS не поддерживает inotify', 'VS Code Remote WSL']" />
</div>

---

# Environments & .env

<div class="accent-line"></div>

- Приоритет `.env` файлов: `env_file: [.env, .env.local]`
- `COMPOSE_PROJECT_NAME` и `COMPOSE_PROFILES` в .env
- `COMPOSE_FILE` с несколькими файлами для overlay-конфигурации
- Интерполяция: `${VAR:-default}`, `${VAR:?error}`
- Переход от `environment:` к `env_file:` для чистоты

---
layout: code-block
---

# env_file: required / optional

```yaml
services:
  app:
    env_file:
      - path: ./default.env
        required: true    # упадёт если нет файла
      - path: ./override.env
        required: false   # опционально — для локальных переопределений
```

Паттерн: `default.env` (в репо) + `override.env` (в .gitignore)

Одинаковые ключи → последний файл в списке побеждает

---

# Приоритет ENV-переменных

<div class="accent-line"></div>

<div style="font-size: 0.85rem;">

|  #  |  `compose run --env`  |  `environment:`  |  `env_file:`  |  Image `ENV` |  Host OS  |  `.env`  |  Result  |
|:--:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
|  1 |  -  |  -  |  -  |  -  |  1.4  |  1.3  |  -  |
|  2 |  -  |  -  |  1.6  |  1.5  |  1.4  |  -  |  **1.6**  |
|  3 |  -  |  1.7  |  -  |  1.5  |  1.4  |  -  |  **1.7**  |
|  4 |  -  |  -  |  -  |  1.5  |  1.4  |  1.3  |  **1.5**  |
|  5 |  1.8  |  -  |  -  |  1.5  |  1.4  |  1.3  |  **1.8**  |

</div>

`.env` НЕ попадает в контейнер — только для интерполяции compose.yml

---
layout: code-block
---

# Lifecycle Hooks

```yaml
services:
  app:
    post_start:
      - command: chown -R www-data:www-data /app/var
      - command: php bin/console cache:warmup
        user: www-data
    pre_stop:
      - command: php bin/console messenger:stop-workers
```

**post_start** — выполняется после старта контейнера (warmup, healthcheck-зависимости)

**pre_stop** — перед остановкой (graceful shutdown воркеров, дамп данных)

---
layout: code-block
---

# Profiles — управление опциональными сервисами

```yaml
services:
  app:                          # без profiles — запускается всегда
    image: php:8.4

  xdebug:
    profiles: [debug]           # только с --profile debug

  mailpit:
    profiles: [debug, test]     # debug ИЛИ test
```

CLI: `docker compose --profile debug up`

ENV: `COMPOSE_PROFILES=debug,monitoring` в `.env`

---

# depends_on: новые условия

<div class="accent-line"></div>

- `service_started` — контейнер запущен (дефолт при `depends_on: servicename`)
- `service_healthy` — healthcheck прошёл ✓
- **`service_completed_successfully`** — завершился с кодом 0 ✓ **(новое!)**

Use case: init-контейнеры — миграции, seed, создание S3-бакетов, индексация в Meilisearch

```yaml
depends_on:
  migrate: { condition: service_completed_successfully }
```

---
layout: compare
---

# Организация compose-файлов

<div class="comparison-grid">
  <CompareCard title="Merge (старый)" :items="['-f compose.yml -f compose.prod.yml', 'Файлы мерджатся поверх друг друга', 'Порядок важен']" />
  <CompareCard title="Extend" :items="['extends: { service: php, file: base.yml }', 'Наследование конкретного сервиса', 'Переиспользование базовых конфигов']" />
  <CompareCard title="Include (новый)" :items="['include: [infra.yml, debug.yml]', 'Модульное подключение целых файлов', 'Изолированные пространства']" />
</div>

---
layout: code-block
---

# YAML-якоря — как делали раньше

```yaml
services:
  php-base: &php-base
    image: php:8.4-fpm
    environment: { APP_ENV: production }
    volumes: [ ./src:/app/src ]

  php:
    <<: *php-base
    ports: [ "9000:9000" ]

  worker:
    <<: *php-base
    command: php bin/console messenger:consume
```

Работает, но: нет наследования между файлами, нечитаемый синтаксис, сложный дебаг

---
layout: code-block
---

# extends — наследование сервисов

```yaml
# base.yml — общая конфигурация
services:
  php-base:
    image: php:8.4-fpm
    environment: { APP_ENV: production }
    volumes: [ ./src:/app/src ]

# compose.yml — наследуем и дополняем
services:
  php:
    extends: { service: php-base, file: base.yml }
    ports: [ "9000:9000" ]

  worker:
    extends: { service: php-base, file: base.yml }
    command: php bin/console messenger:consume
```

---
layout: code-block
---

# include — модульная структура

```yaml
# compose.yml
include:
  - compose/app.yml          # PHP + FrankenPHP
  - compose/database.yml     # PostgreSQL
  - compose/cache.yml        # Valkey
  - compose/mail.yml         # Mailpit
  - compose/search.yml       # Meilisearch
  - compose/storage.yml      # LocalStack S3
  - compose/proxy.yml        # Traefik
  - compose/observability.yml # Grafana LGTM

# Каждый файл — изолированный модуль
# со своими сервисами, volumes, networks
```
