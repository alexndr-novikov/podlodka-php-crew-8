# Ссылки и материалы к воркшопу

Все ссылки проверены через поиск. Сгруппированы по разделам плана.

---

## 1. Docker Compose V2

### Официальная документация
- [Compose Specification Reference](https://docs.docker.com/reference/compose-file/) — полная спецификация compose.yml
- [How Compose works](https://docs.docker.com/compose/intro/compose-application-model/) — модель приложения Compose
- [Docker Compose CLI Reference](https://docs.docker.com/reference/cli/docker/compose/) — команды `docker compose`
- [Compose Release Notes (V5)](https://docs.docker.com/compose/releases/release-notes/) — история изменений

### develop.watch
- [Compose Develop Specification](https://docs.docker.com/reference/compose-file/develop/) — спецификация секции `develop`
- [Use Compose Watch](https://docs.docker.com/compose/how-tos/file-watch/) — руководство по file-watching
- [docker compose watch CLI](https://docs.docker.com/reference/cli/docker/compose/watch/) — справка по команде

### include
- [Include (Compose file reference)](https://docs.docker.com/reference/compose-file/include/) — спецификация `include`
- [Use include to modularize Compose files](https://docs.docker.com/compose/how-tos/multiple-compose-files/include/) — практическое руководство

### profiles
- [Profiles (Compose file reference)](https://docs.docker.com/reference/compose-file/profiles/) — спецификация `profiles`
- [Use service profiles](https://docs.docker.com/compose/how-tos/profiles/) — руководство по использованию

### services, depends_on, healthcheck
- [Define services in Docker Compose](https://docs.docker.com/reference/compose-file/services/) — описание сервисов
- [docker compose up](https://docs.docker.com/reference/cli/docker/compose/up/) — флаг `--wait`

### Переменные окружения
- [Pre-defined environment variables](https://docs.docker.com/compose/how-tos/environment-variables/envvars/) — COMPOSE_PROFILES, COMPOSE_PROJECT_NAME и др.

---

## 2. Dockerfile и BuildKit

### Официальная документация Docker
- [Dockerfile reference](https://docs.docker.com/reference/dockerfile/) — полная справка по инструкциям
- [BuildKit overview](https://docs.docker.com/build/buildkit/) — обзор BuildKit
- [Optimize cache usage in builds](https://docs.docker.com/build/cache/optimize/) — `RUN --mount=type=cache` и другие оптимизации
- [Build secrets](https://docs.docker.com/build/building/secrets/) — `RUN --mount=type=secret` и `--mount=type=ssh`
- [Build cache invalidation](https://docs.docker.com/build/cache/invalidation/) — `COPY --link` и инвалидация кэша
- [Dockerfile release notes](https://docs.docker.com/build/dockerfile/release-notes/) — heredoc и другие новые фичи

### docker-php-extension-installer
- [GitHub: mlocati/docker-php-extension-installer](https://github.com/mlocati/docker-php-extension-installer) — установка PHP-расширений в Docker
- [Docker Hub: mlocati/php-extension-installer](https://hub.docker.com/r/mlocati/php-extension-installer)

---

## 3. Alpine vs Debian — проблемы musl

### iconv
- [Alpine aports issue #15114: php-iconv build with gnu-libiconv](https://gitlab.alpinelinux.org/alpine/aports/-/issues/15114)
- [docker-library/php issue #1121: iconv on Alpine 3.13](https://github.com/docker-library/php/issues/1121)
- [codecasts/php-alpine issue #39: Localisation vs Musl](https://github.com/codecasts/php-alpine/issues/39) — glibc locales, iconv, ICU

### Imagick
- [docker-library/php issue #1033: Cannot install Imagick in Alpine](https://github.com/docker-library/php/issues/1033)
- [Imagick/imagick issue #643: Transient Error Building Imagick on Alpine](https://github.com/Imagick/imagick/issues/643)

### Общие проблемы musl
- [Alpine Linux Users Debate musl vs glibc Compatibility Trade-offs](https://biggo.com/news/202509050742_Alpine_Linux_musl_glibc_compatibility_debate)

---

## 4. Современные PHP-рантаймы

### FrankenPHP
- [FrankenPHP — официальная документация](https://frankenphp.dev/docs/) — обзор, установка, конфигурация
- [FrankenPHP Worker Mode](https://frankenphp.dev/docs/worker/) — документация worker mode
- [FrankenPHP Performance Optimization](https://frankenphp.dev/docs/performance/)
- [FrankenPHP Configuration](https://frankenphp.dev/docs/config/)
- [GitHub: php/frankenphp](https://github.com/php/frankenphp) — исходный код
- [Docker Hub: dunglas/frankenphp](https://hub.docker.com/r/dunglas/frankenphp)
- [Understanding FrankenPHP — thephp.cc](https://thephp.cc/articles/frankenphp) — обзорная статья
- [FrankenPHP Worker Mode Saves Your Sanity — PHP Architect](https://www.phparch.com/2026/01/heres-why-frankenphp-worker-mode-saves-your-sanity/)

### RoadRunner
- [RoadRunner — официальный сайт](https://roadrunner.dev/)
- [RoadRunner Documentation](https://docs.roadrunner.dev/docs)
- [GitHub: roadrunner-server/roadrunner](https://github.com/roadrunner-server/roadrunner)
- [GitHub: roadrunner-php (PHP SDK)](https://github.com/roadrunner-php)
- [RoadRunner — An Underrated Powerhouse for PHP — Medium](https://butschster.medium.com/roadrunner-an-underrated-powerhouse-for-php-applications-46410b0abc)

### Caddy
- [Caddy — официальный сайт](https://caddyserver.com/)
- [php_fastcgi directive](https://caddyserver.com/docs/caddyfile/directives/php_fastcgi) — интеграция с PHP-FPM
- [reverse_proxy directive](https://caddyserver.com/docs/caddyfile/directives/reverse_proxy)
- [Common Caddyfile Patterns](https://caddyserver.com/docs/caddyfile/patterns)

---

## 5. Контейнеры для инфраструктуры

### Mailpit (замена MailHog)
- [GitHub: axllent/mailpit](https://github.com/axllent/mailpit)
- [Mailpit — About](https://mailpit.axllent.org/about/)
- [Mailpit vs MailHog: Which Should You Use in 2026?](https://sendpigeon.dev/blog/mailpit-vs-mailhog)
- [Mailpit, an updated alternative to Mailhog — Chris Wiegman](https://chriswiegman.com/2023/03/mailpit-an-updated-alternative-to-mailhog/)
- [Local Email Debugging with Mailpit — Jeff Geerling](https://www.jeffgeerling.com/blog/2026/mailpit-local-email-debugging/)

### Valkey (форк Redis)
- [Valkey — официальный сайт](https://valkey.io/)
- [Valkey Documentation](https://valkey.io/docs/)
- [GitHub: valkey-io/valkey](https://github.com/valkey-io/valkey)
- [Valkey: A Redis Fork With a Future — The New Stack](https://thenewstack.io/valkey-a-redis-fork-with-a-future/)
- [What is Valkey? — DanubeData](https://danubedata.ro/blog/what-is-valkey-redis-fork-2025)

### DragonflyDB
- [DragonflyDB — официальный сайт](https://www.dragonflydb.io/)
- [DragonflyDB Documentation](https://www.dragonflydb.io/docs)
- [Getting Started](https://www.dragonflydb.io/docs/getting-started)
- [GitHub: dragonflydb/dragonfly](https://github.com/dragonflydb/dragonfly)

### LocalStack
- [LocalStack — официальный сайт](https://www.localstack.cloud/)
- [LocalStack Documentation](https://docs.localstack.cloud/)
- [Getting Started with AWS services](https://docs.localstack.cloud/aws/getting-started/)
- [GitHub: localstack/localstack](https://github.com/localstack/localstack)
- [AWS development with LocalStack — Docker Docs](https://docs.docker.com/guides/localstack/)

### Поисковые движки

**Meilisearch:**
- [Meilisearch — официальный сайт](https://www.meilisearch.com/)
- [Meilisearch vs Typesense](https://www.meilisearch.com/blog/meilisearch-vs-typesense)

**Typesense:**
- [Typesense — официальный сайт](https://typesense.org/)

**Manticore Search:**
- [Manticore Search Manual](https://manual.manticoresearch.com/)
- [Manticore Search — официальный сайт](https://manticoresearch.com/)
- [Manticore Search vs Meilisearch](https://manticoresearch.com/blog/manticoresearch-vs-meilisearch/)

### Очереди и pub/sub

**Kafka (KRaft mode):**
- [Docker Hub: apache/kafka](https://hub.docker.com/r/apache/kafka) — официальный образ
- [Docker Hub: apache/kafka-native](https://hub.docker.com/r/apache/kafka-native) — нативный образ
- [Setting Up a Kafka Cluster Using Docker Compose (KRaft Mode)](https://medium.com/@darshak.kachchhi/setting-up-a-kafka-cluster-using-docker-compose-a-step-by-step-guide-a1ee5972b122)

**NATS:**
- [NATS — официальный сайт](https://nats.io/)
- [Compare NATS with other systems](https://docs.nats.io/nats-concepts/overview/compare-nats)
- [Beyond Kafka and Redis: A Practical Guide to NATS — Dev.to](https://dev.to/thedonmon/beyond-kafka-and-redis-a-practical-guide-to-nats-as-your-unified-cloud-native-backbone-4g86)

### Reverse proxy
**Traefik:**
- [Docker Hub: traefik](https://hub.docker.com/_/traefik) — официальный образ
- [Traefik v3 Docker Provider](https://doc.traefik.io/traefik/v3.3/providers/docker/)
- [Traefik Docker basic example](https://doc.traefik.io/traefik/user-guides/docker-compose/basic-example/)

---

## 6. Отладка и Observability

### Buggregator
- [Buggregator — официальный сайт](https://buggregator.dev/)
- [Buggregator Documentation](https://docs.buggregator.dev/trap/getting-started.html)
- [GitHub: buggregator/trap](https://github.com/buggregator/trap) — CLI-версия
- [Packagist: buggregator/trap](https://packagist.org/packages/buggregator/trap)
- [Debug PHP Smarter: Introducing Buggregator — Medium](https://butschster.medium.com/buggregator-is-a-free-779e7ad5fe12)

### OpenTelemetry + Grafana
- [Instrument a PHP application — Grafana OpenTelemetry docs](https://grafana.com/docs/opentelemetry/instrument/php/)
- [OpenTelemetry at Grafana Labs](https://grafana.com/docs/opentelemetry/)
- [GitHub: grafana/docker-otel-lgtm](https://github.com/grafana/docker-otel-lgtm) — Loki+Grafana+Tempo+Mimir в одном контейнере
- [Introducing grafana/otel-lgtm — Grafana Blog](https://grafana.com/blog/an-opentelemetry-backend-in-a-docker-image-introducing-grafana-otel-lgtm/)

### Gotenberg (PDF)
- [Gotenberg — официальный сайт](https://gotenberg.dev/)
- [Getting Started](https://gotenberg.dev/docs/getting-started/introduction)
- [GitHub: gotenberg/gotenberg](https://github.com/gotenberg/gotenberg)

---

## 7. Temporal

- [Temporal PHP SDK Developer Guide](https://docs.temporal.io/develop/php) — официальная документация
- [Core Application — PHP SDK](https://docs.temporal.io/develop/php/core-application) — Workflows, Activities, Workers
- [GitHub: temporalio/sdk-php](https://github.com/temporalio/sdk-php)
- [Testing — PHP SDK](https://docs.temporal.io/develop/php/testing-suite)

---

## 8. Аудит и безопасность образов

- [Hadolint — официальный сайт](https://hadolint.com/) — линтер Dockerfile
- [Trivy — GitHub](https://github.com/aquasecurity/trivy) — сканер уязвимостей
- [Docker Scout — Docker Docs](https://docs.docker.com/scout/) — встроенный анализ образов

---

## 9. Docker Desktop и альтернативы

- [OrbStack — официальный сайт](https://orbstack.dev/) — быстрая альтернатива Docker Desktop для macOS
- [OrbStack vs Docker Desktop](https://orbstack.dev/docs/compare/docker-desktop)
- [Podman Desktop](https://podman-desktop.io/) — open-source альтернатива
- [Docker Desktop Alternatives 2025: Podman, OrbStack, Colima](https://fsck.sh/en/blog/docker-desktop-alternatives-2025/)

---

## 10. Экосистема фреймворков

### Laravel
- [Laravel Sail — официальная документация](https://laravel.com/docs/12.x/sail)
- [GitHub: laravel/sail](https://github.com/laravel/sail)
- [A Complete Guide to Laravel Sail — osteel's blog](https://tech.osteel.me/posts/you-dont-need-laravel-sail)

### Symfony
- [GitHub: dunglas/symfony-docker](https://github.com/dunglas/symfony-docker) — Docker-стек на FrankenPHP
- [Symfony Docker — Dockerfile](https://github.com/dunglas/symfony-docker/blob/main/Dockerfile) — пример production-ready Dockerfile
- [FrankenPHP Docker documentation](https://github.com/php/frankenphp/blob/main/docs/docker.md)

### DDEV
- [DDEV — официальный сайт](https://ddev.com/)
- [DDEV Documentation](https://docs.ddev.com/en/stable/)
- [GitHub: ddev/ddev](https://github.com/ddev/ddev)
