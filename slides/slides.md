---
theme: ./theme
title: "Docker для PHP-разработчика в 2026"
info: "Podlodka PHP Crew 8"
fonts:
  sans: Lexend
  serif: Lexend
  mono: JetBrains Mono
  provider: google
drawings:
  persist: false
transition: slide-left
---

# Docker для PHP‑разработчика в 2026

Локальное окружение, актуальные практики, современные контейнеры

<div class="abs-bl m-6 text-sm" style="color: var(--text-muted); left: 4rem;">
Александр Новиков · Podlodka PHP Crew 8
</div>

---

# «Зачем доклад, если есть ChatGPT?»

<div class="accent-line"></div>

- LLM **не сгенерировал треть тем** из этого доклада — даже при прямом запросе
- Каждая итерация пересборки Docker с AI-агентом **ещё дольше**: агент думает, читает логи, пробует варианты
- Лучше **сразу знать что хочешь** и скормить в контекст, чем итерировать вслепую
- Системное знание Docker экономит время **особенно** при работе с AI

---

# Что нас ждёт

<div class="two-col-custom">
<div>

- Эволюция Docker Compose
- Современный Dockerfile для PHP
- PHP-рантаймы: FrankenPHP, RoadRunner
- Контейнеры для инфраструктуры
- Разработка и отладка
- Фоновые задачи

</div>
<div>

- Observability Stack
- CI/CD и тестирование
- Docker Desktop и альтернативы
- Референсные реализации
- Best Practices 2026
- Live coding воркшоп

</div>
</div>

---
src: ./01-evolution.md
---

---
src: ./02-dockerfile.md
---

---
src: ./03-runtimes.md
---

---
src: ./04-infrastructure.md
---

---
src: ./05-development.md
---

---
src: ./06-background.md
---

---
src: ./07-observability.md
---

---
src: ./08-cicd.md
---

---
src: ./09-desktop.md
---

---
src: ./10-references.md
---

---
src: ./11-bestpractices.md
---

---
src: ./12-workshop.md
---
