# Docker для PHP-разработчика: что изменилось за 3 года

Демо-приложение для воркшопа **Podlodka PHP Crew 8**.

Symfony 7.2 + FrankenPHP + полный стек инфраструктуры в Docker. Разворачивается одной командой.

## Требования

- Docker Desktop (или OrbStack) с Docker Compose V2
- [mkcert](https://github.com/FiloSottile/mkcert) — для локальных TLS-сертификатов
- Make

## Быстрый старт

```bash
make setup   # генерация TLS-сертификатов + .env.local
make up      # запуск всех сервисов
```

## Сервисы

| Сервис | URL | Описание |
|--------|-----|----------|
| Symfony App | https://app.workshop.localhost:8443 | Основное приложение |
| Mailpit | https://mailpit.workshop.localhost:8443 | Тестирование email |
| Grafana | https://grafana.workshop.localhost:8443 | Логи, трейсы, метрики |
| Meilisearch | https://search.workshop.localhost:8443 | Полнотекстовый поиск |
| Temporal UI | https://temporal.workshop.localhost:8443 | Управление workflow |
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
| Объектное хранилище | LocalStack (S3) |
| Workflow engine | Temporal |
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
  storage.yml                <- LocalStack (S3)
  temporal.yml               <- Temporal server + UI + PHP worker
  observability.yml          <- Grafana LGTM
  tunnel.yml                 <- Cloudflare / ngrok (profile)
  debug.yml                  <- Buggregator (profile)
  test.yml                   <- Selenium (profile)
```
