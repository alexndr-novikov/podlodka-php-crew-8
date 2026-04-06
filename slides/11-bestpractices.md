---
layout: section
sectionNumber: '11'
---

# Best Practices 2026

---

# Архитектура и конфигурация

<div class="accent-line"></div>

- Один процесс — один контейнер
- Healthcheck для каждого сервиса
- Named volumes для данных, bind mounts для кода
- `.dockerignore` — обязательно
- Multi-stage builds: `dev → test → production`
- Pinning версий образов до минорной версии

---
layout: default
class: bg-purple-50/30
---

# Workflow и инструменты

<div class="accent-line"></div>

- `develop.watch` вместо сторонних file-watchers
- `profiles` для опциональных сервисов
- Compose `include` для модульности
- Кэширование зависимостей через `--mount=type=cache`
- Non-root user в контейнерах
- `.env` + `.env.local` для конфигурации

---
layout: code-block
---

# Makefile / Taskfile / Just

```makefile
# Makefile — алиасы для частых команд
up:
	docker compose up -d --wait

down:
	docker compose down

shell:
	docker compose exec php sh

test:
	docker compose run --rm php vendor/bin/phpunit

lint:
	docker compose run --rm php vendor/bin/phpstan analyse
```
