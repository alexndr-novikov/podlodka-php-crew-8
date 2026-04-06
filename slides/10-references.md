---
layout: section
sectionNumber: '10'
---

# Референсные реализации

---

# Laravel

<div class="accent-line"></div>

- **Laravel Sail** — официальное Docker-окружение (MySQL/PG/Redis/Meilisearch/Mailpit)
- **Laravel Octane** — FrankenPHP / RoadRunner / Swoole в production-like
- **Laravel Herd** — нативная локальная среда (без Docker)
- PostgreSQL — default DB в Laravel 11+

---
layout: default
class: bg-purple-50/30
---

# Symfony

<div class="accent-line"></div>

- **dunglas/symfony-docker** — официальный стек на FrankenPHP
- **Symfony CLI** — встроенный локальный сервер
- **Symfony Messenger** — очереди через RabbitMQ/Redis/Doctrine
- **Symfony Runtime** — интеграция с FrankenPHP/RoadRunner

---

# Универсальные инструменты

<div class="accent-line"></div>

<div class="two-col-custom">
  <CompareCard title="DDEV" :items="['Docker-окружение для PHP', 'Drupal, WordPress, Laravel, Symfony', 'Простая настройка']" />
  <CompareCard title="Lando" :items="['Docker-based dev environment', 'Множество рецептов', 'Extensible']" />
</div>
