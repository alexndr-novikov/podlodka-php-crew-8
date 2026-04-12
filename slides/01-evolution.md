---
layout: section
sectionNumber: '01'
---

# Эволюция Docker Compose

---

<div class="flex flex-col items-center">
  <div class="opacity-20 line-through" style="font-family: 'JetBrains Mono', monospace; font-size: 3.5rem; font-weight: 700; color: var(--text-muted);">docker-compose.yml</div>
  <div class="mt-4" style="font-family: 'JetBrains Mono', monospace; font-size: 3.5rem; font-weight: 700; color: var(--purple);">compose.yml</div>
  <div class="mt-8 text-xl" style="color: var(--text-secondary);">Новое каноничное имя файла. docker-compose.yml всё ещё работает, но deprecated.</div>
</div>

---

<div class="flex flex-col items-center">
  <div class="opacity-20 line-through" style="font-family: 'JetBrains Mono', monospace; font-size: 3.5rem; font-weight: 700; color: var(--text-muted);">docker-compose up</div>
  <div class="mt-4" style="font-family: 'JetBrains Mono', monospace; font-size: 3.5rem; font-weight: 700; color: var(--purple);">docker compose up</div>
  <div class="mt-8 text-xl text-center" style="color: var(--text-secondary);">V1 (Python) → V2 (Go). Отдельный бинарник → плагин Docker CLI. 2024: V1 окончательно удалён.</div>
</div>

---

# Формат и CLI

<div class="accent-line"></div>

- `compose.yml` вместо `docker-compose.yml` — новое каноничное имя
- `docker compose` (встроенная команда) вместо `docker-compose` (deprecated)
- Compose V2 — Go-реализация, плагин Docker CLI
- `docker compose watch` — встроенный file-watching
- `docker compose up --watch` — запуск + watch в одной команде
- `docker compose up --wait` — ожидание healthcheck
- `docker compose alpha dry-run` — предпросмотр без запуска

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
- `depends_on` с `condition: service_healthy`
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

---

# Приоритет ENV-переменных

<div class="accent-line"></div>

<div style="font-size: 1.1rem;">

| Приоритет | Источник |
|:---------:|----------|
| 1 (высший) | `docker compose run --env` |
| 2 | `environment:` в compose.yml |
| 3 | `env_file:` в compose.yml |
| 4 | `ENV` в Dockerfile |
| 5 | Host OS переменные окружения |
| 6 (низший) | `.env` файл |

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

- `service_started` — контейнер запущен (как раньше)
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
