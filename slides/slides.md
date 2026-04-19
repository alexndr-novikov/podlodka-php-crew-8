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
highlighter: shiki
layout: cover
class: p-0
---

<img src="/PHP8_Novikov.png" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;" />

---

# «Зачем доклад, если есть LLM?»

<div class="accent-line"></div>

- LLM **не сгенерировал половину тем** из этого доклада
- Docker build + agent think = **долго х 2**
- Лучше **сразу знать что хочешь** и скормить в контекст, чем итерировать вслепую
- Системное знание Docker экономит время **особенно** при работе с AI
- Знать самому всегда приятнее :)

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

- CI/CD и тестирование
- Docker Desktop и альтернативы
- Референсные реализации
- Best Practices 2026
- Демонстрация проекта с реализацией советов

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
