---
layout: section
sectionNumber: '06'
---

# Фоновые задачи

---

# Temporal

<div class="accent-line"></div>

- `temporalio/auto-setup` — сервер с автоматической настройкой
- `temporalio/ui` — веб-интерфейс
- PHP SDK (`temporal/sdk`) — workflow и activity
- Temporal server + PHP worker в отдельном контейнере

---
layout: default
class: bg-purple-50/30
---

# Worker-процессы

<div class="accent-line"></div>

<div class="two-col-custom">
<div>

### Supervisord внутри

- Один контейнер, несколько процессов
- Проще для небольших проектов

</div>
<div>

### Отдельные контейнеры ✓

- Предпочтительнее (один процесс = один контейнер)
- `php artisan queue:work`
- `messenger:consume`

</div>
</div>
