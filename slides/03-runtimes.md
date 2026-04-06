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

# Сравнение подходов

<div class="accent-line"></div>

<table class="slide-table">
  <thead>
    <tr><th>Стек</th><th>Плюсы</th><th>Минусы</th></tr>
  </thead>
  <tbody>
    <tr><td><strong>Nginx + PHP-FPM</strong></td><td>Проверенный, привычный</td><td>Два процесса, конфигурация</td></tr>
    <tr><td><strong>FrankenPHP</strong></td><td>Всё в одном, HTTPS, HTTP/3</td><td>Молодой проект</td></tr>
    <tr><td><strong>RoadRunner</strong></td><td>gRPC, WS, очереди из коробки</td><td>Утечки памяти при ошибках</td></tr>
    <tr><td><strong>Swoole/OpenSwoole</strong></td><td>Async PHP, высокая пропускная способность</td><td>Несовместимость с частью библиотек</td></tr>
  </tbody>
</table>

---
layout: default
class: bg-purple-50/30
---

# Caddy как reverse proxy

<div class="accent-line"></div>

- Замена Nginx / Traefik для локальной разработки
- Автоматический HTTPS для локальных доменов
- Caddyfile — простая декларативная конфигурация
- Связка: Caddy → PHP-FPM или Caddy через FrankenPHP
