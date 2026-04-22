---
layout: section
sectionNumber: '03'
---

# Современные PHP-рантаймы

---

# FrankenPHP

<div class="accent-line"></div>

- Встроенный app server на Go (на базе Caddy)
- Worker mode — PHP-процессы переживают запросы
- HTTP/2, HTTP/3, автоматический HTTPS
- Early Hints (103), Mercure (real-time), Vulcain (preloading)
- Образ: `dunglas/frankenphp`
- Интеграция: Symfony Runtime, Laravel Octane

---
layout: default
class: bg-purple-50/30
---

# RoadRunner

<div class="accent-line"></div>

- Application server на Go с PHP-воркерами
- gRPC, WebSocket, Centrifuge, Jobs (очереди), KV, Metrics
- Образ: `ghcr.io/roadrunner-server/roadrunner`
- Spiral Framework, Laravel Octane, Symfony

---
layout: default
class: bg-purple-50/30
---

# Caddy как reverse proxy

<div class="accent-line"></div>

- Написан на Go, один бинарник — zero dependencies
- Автоматический HTTPS для локальных доменов
- Caddyfile — простая декларативная конфигурация
- Связка: Caddy → PHP-FPM или Caddy через FrankenPHP
