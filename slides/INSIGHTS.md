# Инсайты для новых слайдов

Собираем заметки по ходу чтения документации. Потом превратим в слайды.

---

## 1. Post-start hooks в Docker Compose
- Новая фича: хуки, выполняемые после старта контейнера
- Позволяет запускать команды/скрипты автоматически после поднятия сервиса

## 2. Pre-stop hooks в Docker Compose
- Хуки, выполняемые перед остановкой контейнера
- Graceful shutdown, cleanup, дамп данных и т.д.

## 3. Profiles — подробнее
- Определение в yml: `profiles: [debug]` на уровне сервиса
- Запуск в CLI: `docker compose --profile debug up`
- ENV переменная: `COMPOSE_PROFILES=debug,monitoring` в `.env`
- Сервисы без profiles запускаются всегда
- Можно комбинировать несколько профилей
- Полезные COMPOSE_* переменные:
  - `COMPOSE_PROFILES=debug,monitoring` — активировать профили через .env
  - `COMPOSE_DISABLE_ENV_FILE=true` — полностью отключить загрузку .env файла (полезно в CI)

## 4. depends_on: service_completed_successfully
- Новое условие в `depends_on.condition`
- Ждёт пока зависимый сервис завершится с кодом 0
- Идеально для init-контейнеров: миграции БД, seed данных, создание S3-бакетов, первичная индексация в поисковый движок (Meilisearch, Elasticsearch)
- Use case: app стартует только после того как migrate-контейнер успешно накатил схему
- Пример: `depends_on: { migrate: { condition: service_completed_successfully } }`
- Раньше было только `service_started` и `service_healthy`

## 5. env_file с required/optional
- Можно указывать несколько env-файлов с флагом `required`
- `required: false` — файл опционален, не упадёт если отсутствует
- Паттерн: default.env (committed) + override.env (local, в .gitignore)
```yaml
env_file:
  - path: ./default.env
    required: true   # default
  - path: ./override.env
    required: false  # не упадёт если нет файла
```

## 6. Приоритет ENV-переменных в Compose
- Источник: https://docs.docker.com/compose/how-tos/environment-variables/envvars-precedence/
- Порядок приоритета (от высшего к низшему):
  1. `docker compose run --env`
  2. `environment:` в compose.yml
  3. `env_file:` в compose.yml
  4. Image `ENV` (из Dockerfile)
  5. Host OS environment
  6. `.env` файл
- Полная таблица с примерами:

|  # |  `compose run --env`  |  `environment`  |  `env_file`  |  Image `ENV` |  Host OS  |  `.env`  |  Result  |
|:--:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
|  1 |  -  |  -  |  -  |  -  |  1.4  |  1.3  |  -  |
|  2 |  -  |  -  |  1.6  |  1.5  |  1.4  |  -  |  **1.6**  |
|  3 |  -  |  1.7  |  -  |  1.5  |  1.4  |  -  |  **1.7**  |
|  4 |  -  |  -  |  -  |  1.5  |  1.4  |  1.3  |  **1.5**  |
|  5 |  1.8  |  -  |  -  |  1.5  |  1.4  |  1.3  |  **1.8**  |

- Важно: `.env` файл НЕ попадает в контейнер напрямую — только для интерполяции compose.yml
- Host OS env побеждает `.env` при подстановке в compose.yml

## 7. Подходы к организации нескольких compose-файлов
- **Merge** (старый подход): `docker compose -f compose.yml -f compose.prod.yml up` — файлы мерджатся поверх друг друга
- **Extend**: `extends: { service: php, file: compose.base.yml }` — наследование конкретного сервиса из другого файла
- **Include** (новый): `include: [./compose.infra.yml, ./compose.debug.yml]` — модульное подключение целых файлов
- Include vs Merge: include изолирует (отдельные пространства), merge перезаписывает
- Рекомендация: include для модульности, extend для переиспользования базовых сервисов

## 8. GPU support в Docker Compose
- Источник: https://docs.docker.com/compose/how-tos/gpu-support/
- Нативная поддержка GPU через `deploy.resources.reservations.devices`
- Актуально для AI/ML: локальный inference (Ollama, vLLM, llama.cpp), обучение моделей
- PHP-контекст: генерация embeddings, OCR с GPU-ускорением, AI-микросервисы рядом с приложением
```yaml
services:
  ollama:
    image: ollama/ollama
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: 1
              capabilities: [gpu]
```

## 9. Docker AI Agent (Gordon)
- Источник: https://docs.docker.com/ai/docker-agent/
- Встроенный AI-агент в Docker Desktop — помощник для работы с контейнерами
- Умеет: генерировать Dockerfile, отлаживать проблемы, объяснять ошибки сборки
- Не в скоупе доклада, но упомянуть как тренд: Docker движется в сторону AI-first тулинга

## 10. Docker MCP Toolkit
- Docker Desktop расширение для запуска MCP-серверов в контейнерах
- MCP (Model Context Protocol) — стандарт Anthropic для подключения AI-агентов к инструментам
- Docker MCP Toolkit: каталог MCP-серверов, запуск в изоляции, управление через UI
- Связка с Gordon: AI-агент может использовать MCP-серверы для доступа к БД, API, файлам
- Актуально: MCP быстро становится стандартом для AI-интеграций, Docker упрощает деплой серверов

## 11. Зачем этот доклад когда есть AI (ПЕРВЫЙ СЛАЙД после титульного)
- LLM не сгенерировал добрую треть тем из этого доклада — даже при прямом запросе
- Каждая итерация пересборки Docker с AI-агентом становится ещё дольше: агент думает, читает логи, пробует варианты
- Лучше сразу знать что хочешь и скормить в контекст, чем итерировать вслепую
- Вывод: системное знание Docker экономит время даже (особенно!) при работе с AI

## 12. `docker init`
- Интерактивный генератор: Dockerfile + compose.yml + .dockerignore
- Поддерживает PHP (и Go, Python, Node, Rust, Java, .NET)
- `docker init` в папке проекта → отвечаешь на вопросы → готовый стартовый набор
- Огромный DX-win для новых проектов и onboarding

## 13. `docker debug`
- Присоединение debug-shell к любому запущенному контейнеру
- Работает даже с distroless/минимальными образами где нет sh/bash
- Инжектит инструменты (vim, curl, htop и т.д.) на лету, не меняя образ
- `docker debug <container>` — и ты внутри с полным набором утилит

## 14. `docker compose up --watch`
- Шортхенд: совмещает `up -d` и `watch` в одной команде
- Раньше: `docker compose up -d && docker compose watch`
- Теперь: `docker compose up --watch` — одна команда для dev-окружения

## 15. Testcontainers для PHP
- Интеграционное тестирование с реальными контейнерами прямо из PHPUnit
- Библиотека `testcontainers-php` — поднимает PostgreSQL, Redis, Meilisearch на лету
- Каждый тест получает чистый контейнер, нет shared state
- Замена моков: тестируем с настоящей БД, а не с фейковой
- Тренд 2025-2026: Testcontainers стал стандартом для integration tests

## 16. Compose `name:` top-level property
- Задаёт имя проекта прямо в compose.yml: `name: my-project`
- Убирает зависимость от имени директории
- Раньше: `COMPOSE_PROJECT_NAME` в .env или `-p` флаг
- Теперь: явно в файле, коммитится в репо, одинаково у всех в команде

