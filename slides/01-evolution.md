---
layout: section
sectionNumber: '01'
---

# Эволюция Docker Compose

---

# Формат и CLI

<div class="accent-line"></div>

- `compose.yml` вместо `docker-compose.yml` — новое каноничное имя
- `docker compose` (встроенная команда) вместо `docker-compose` (deprecated)
- Compose V2 — Go-реализация, плагин Docker CLI
- `docker compose watch` — встроенный file-watching
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
