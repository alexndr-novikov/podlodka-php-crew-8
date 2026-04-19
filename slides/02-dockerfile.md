---
layout: section
sectionNumber: '02'
---

# Современный Dockerfile для PHP

---
layout: default
class: bg-purple-50/30
---

# Базовые образы

<div class="accent-line"></div>

- Официальные: `php:8.3-fpm`, `php:8.4-fpm`, `php:8.4-cli`
- Фиксация версий: `php:8.4.2-fpm-bookworm` вместо `php:8.4-fpm`
- `FROM --platform=$BUILDPLATFORM` для multi-arch сборки
- Alpine ~50 MB vs Debian ~250 MB, но разница сокращается после расширений

---

# Alpine vs Debian: подводные камни

<div class="accent-line"></div>

- **musl vs glibc** — корень большинства проблем
- **Imagick:** отсутствие шрифтов, ICC-профилей, HEIC/AVIF delegates
- **DNS:** musl не поддерживает search/ndots как glibc
- **iconv:** урезанная реализация, проблемы с CP1251, KOI8-R
- **malloc:** musl медленнее под нагрузкой (решение: jemalloc)
- **PECL:** grpc, protobuf могут не компилироваться

---
layout: code-block
---

# BuildKit фичи

```dockerfile
# Кэш Composer между сборками
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev

# Безопасная передача токенов
RUN --mount=type=secret,id=composer_auth \
    composer config -g github-oauth ...

# Копирование без инвалидации слоёв
COPY --link --chmod=755 . /app

# Heredoc — многострочные скрипты
RUN <<EOF
  apt-get update && apt-get install -y libpq-dev
  docker-php-ext-install pdo_pgsql
EOF
```

---
layout: default
class: bg-purple-50/30
---

# PHP-расширения

<div class="accent-line"></div>

- `docker-php-extension-installer` (mlocati) — де-факто стандарт
- Типичный набор: <span class="tag">pdo_pgsql</span> <span class="tag">redis</span> <span class="tag">intl</span> <span class="tag">gd</span> <span class="tag">zip</span> <span class="tag">opcache</span>
- Для разработки: <span class="tag tag-teal">xdebug</span> <span class="tag tag-teal">pcov</span> <span class="tag tag-teal">excimer</span>
- Разделение dev/prod через multi-stage targets

