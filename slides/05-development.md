---
layout: section
sectionNumber: '05'
---

# Разработка и отладка

---
layout: code-block
---

# Xdebug 3

```yaml
# Настройка через переменные окружения
environment:
  XDEBUG_MODE: debug       # debug | profile | trace | coverage
  XDEBUG_CONFIG: "client_host=host.docker.internal"

# Включение через profiles
services:
  php-debug:
    extends: php
    profiles: [debug]
    build:
      target: dev
```

---

# `docker debug` — отладка любого контейнера

<div class="accent-line"></div>

- Присоединяет debug-shell к **любому** запущенному контейнеру
- Работает даже с **distroless / минимальными** образами (без sh/bash)
- Инжектит утилиты (vim, curl, htop, strace) на лету, не меняя образ
- `docker debug <container>` — и ты внутри с полным набором инструментов

Идеально для production-like контейнеров на базе `php:8.4-cli` без лишних пакетов

---
layout: default
class: bg-purple-50/30
---

# Инструменты качества кода

<div class="accent-line"></div>

- **PHPStan / Psalm** — статический анализ
- **PHP CS Fixer / PHP_CodeSniffer** — code style
- **Rector** — автоматический рефакторинг
- Запуск через `docker compose run` или как отдельные сервисы
