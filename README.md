# Docker для PHP-разработчика: что изменилось, пока вы не смотрели

Репозиторий к докладу **«Docker для PHP: что изменилось, пока вы не смотрели»** на [Podlodka PHP Crew 8](https://podlodka.io/phpcrew) (20–24 апреля 2026).

Содержит:
- **Демо-приложение** — Symfony 7.2 + FrankenPHP + полный стек инфраструктуры в Docker
- **Слайды** — презентация на Slidev

## Требования

- Docker Desktop (или OrbStack) с Docker Compose V2
- [mkcert](https://github.com/FiloSottile/mkcert) — для локальных TLS-сертификатов
- Make
- Node.js 18+ (для слайдов)

## Быстрый старт

```bash
make setup   # генерация TLS-сертификатов + .env.local
make up      # запуск всех сервисов
```

## Слайды

```bash
make slides        # запуск dev-сервера (http://localhost:3030)
make slides-build  # сборка статичного SPA
make slides-export # экспорт в PDF
make slides-share  # расшаривание через Cloudflare Tunnel
```

Исходники слайдов — в директории `slides/`, построены на [Slidev](https://sli.dev/).

## Сервисы

| Сервис | URL | Описание |
|--------|-----|----------|
| Symfony App | https://app.workshop.localhost:8443 | Основное приложение |
| Mailpit | https://mailpit.workshop.localhost:8443 | Тестирование email |
| Grafana | https://grafana.workshop.localhost:8443 | Логи, трейсы, метрики |
| Meilisearch | https://search.workshop.localhost:8443 | Полнотекстовый поиск |
| S3 Manager | https://s3.workshop.localhost:8443 | UI для LocalStack S3 |
| Traefik | https://traefik.workshop.localhost:8443/dashboard/ | Маршрутизация |

> Порты 80/443 можно изменить через `HTTP_PORT` / `HTTPS_PORT` в `.env`.

## Стек

| Компонент | Технология |
|-----------|-----------|
| Application server | FrankenPHP (Caddy + embedded PHP 8.4) |
| Reverse proxy | Traefik v3 + mkcert |
| База данных | PostgreSQL 17 |
| Кэш / очереди | Valkey 8 |
| Поиск | Meilisearch v1 |
| Почта | Mailpit |
| Объектное хранилище | LocalStack (S3) + S3 Manager |
| Observability | Grafana LGTM (OTel Collector + Loki + Tempo + Mimir) |

## Опциональные сервисы (profiles)

```bash
make debug         # Buggregator — PHP debug server
make tunnel        # Cloudflare Tunnel — публичный URL для вебхуков
make tunnel-ngrok  # ngrok — альтернативный туннель
```

## Команды

```bash
make help       # Все доступные команды
make up         # Запустить
make down       # Остановить
make logs       # Логи всех сервисов
make shell      # Bash в контейнере приложения
make watch      # File watching (hot-reload)
make lint       # PHPStan + CS Fixer
make test       # PHPUnit
make composer ARGS="require foo/bar"   # Composer
make console ARGS="cache:clear"        # Symfony Console
make reset      # Полный сброс: volumes + rebuild + migrate + seed
```

## Архитектура Docker

```
compose.yml                  <- include всех модулей
compose/
  proxy.yml                  <- Traefik (TLS, маршрутизация)
  app.yml                    <- FrankenPHP + Messenger worker
  database.yml               <- PostgreSQL
  cache.yml                  <- Valkey
  search.yml                 <- Meilisearch
  mail.yml                   <- Mailpit
  storage.yml                <- LocalStack (S3) + S3 Manager
  observability.yml          <- Grafana LGTM
  tunnel.yml                 <- Cloudflare / ngrok (profile)
  debug.yml                  <- Buggregator (profile)
  test.yml                   <- Selenium (profile)
```
