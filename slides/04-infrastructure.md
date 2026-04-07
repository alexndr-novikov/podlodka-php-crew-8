---
layout: section
sectionNumber: '04'
---

# Контейнеры для инфраструктуры

---
layout: compare
---

# Базы данных

<div class="comparison-grid">
  <CompareCard title="PostgreSQL 17" :items="['Default в Laravel 11+', 'pg_isready healthcheck']" />
  <CompareCard title="MySQL 8.4 / 9.0" :items="['mysqladmin ping', 'Классический выбор']" />
  <CompareCard title="MariaDB 11" :items="['Open-source форк', 'Совместимость с MySQL']" />
</div>

- Named volumes для персистентности
- Инициализация через `/docker-entrypoint-initdb.d/`

---
layout: compare
alt: true
---

# Кэш: Redis и альтернативы

<div class="comparison-grid">
  <CompareCard title="Redis 7" :items="['redis-stack для RedisInsight', 'Классика, проверенный']" />
  <CompareCard title="Valkey 8" :items="['Fork Redis (open-source)', 'Drop-in замена']" />
  <CompareCard title="DragonflyDB" :items="['Высокопроизводительная', 'Redis-совместимая']" />
  <CompareCard title="KeyDB" :items="['Многопоточный', 'Redis-совместимый']" />
</div>

---
layout: compare
---

# Очереди

<div class="comparison-grid">
  <CompareCard title="RabbitMQ 4" :items="['management UI', 'AMQP стандарт']" />
  <CompareCard title="Apache Kafka" :items="['KRaft mode (без ZooKeeper)', 'bitnami/kafka']" />
  <CompareCard title="NATS 2" :items="['Легковесная', 'Pub/sub + очереди']" />
</div>

---
layout: compare
alt: true
---

# Поиск

<div class="comparison-grid">
  <CompareCard title="Meilisearch v1" description="Рекомендация Laravel Scout. Простой, быстрый" />
  <CompareCard title="Typesense 27" description="Альтернатива. Typo-tolerance из коробки" />
  <CompareCard title="Elasticsearch 8" description="Для сложных случаев. Full-text + аналитика" />
  <CompareCard title="Manticore" description="Лёгкая альтернатива. Бывший Sphinx" />
</div>

---

# Почта: Mailpit

<div class="accent-line"></div>

- `axllent/mailpit` — замена MailHog (unmaintained с 2022)
- Современный UI, HTML-preview, поддержка POP3
- SMTP на порту <span class="tag">1025</span>, веб-интерфейс на <span class="tag">8025</span>
- API для интеграционных тестов

---
layout: default
class: bg-purple-50/30
---

# S3: LocalStack vs MinIO

<div class="accent-line"></div>

<div class="two-col-custom">
  <CompareCard title="LocalStack" :items="['S3, SQS, SNS, DynamoDB, Lambda', 'Полная эмуляция AWS', 'Community edition бесплатен']" />
  <CompareCard title="MinIO" :items="['Чистый S3-совместимый', 'Проще в настройке', 'Легче по ресурсам']" />
</div>

Только S3 → MinIO. Нужны SQS/SNS/Lambda → LocalStack.

---

# ⚠️ LocalStack: конец эпохи

<div class="accent-line"></div>

- **Февраль 2025** — LocalStack объявил о закрытии Community Edition
- Проект переходит на полностью коммерческую модель
- AWS запустил официальный форк — **Floci** (`github.com/hectorvent/floci`)
- Floci поддерживается AWS, совместим с LocalStack API
- **Вывод:** для новых проектов — MinIO (S3) или Floci (полный AWS)

---
layout: compare
---

# Reverse Proxy

<div class="comparison-grid">
  <CompareCard title="Caddy 2" :items="['Автоматический HTTPS', 'Простая конфигурация']" />
  <CompareCard title="Traefik v3" :items="['Service discovery по Docker labels', 'Автоматический роутинг']" />
  <CompareCard title="Nginx Proxy Manager" :items="['UI для управления', 'Знакомый nginx']" />
</div>

Локальные домены: `*.localhost`, `*.test` + `mkcert` для HTTPS
