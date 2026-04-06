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
layout: compare
---

# Профилирование и мониторинг

<div class="comparison-grid">
  <CompareCard title="Excimer" description="Low-overhead profiling. Используется Wikipedia" />
  <CompareCard title="SPX" description="Простой профилировщик с веб-UI" />
  <CompareCard title="Buggregator" description="All-in-one: Xdebug, VarDumper, Ray, SMTP, Sentry, Profiler" />
</div>

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
