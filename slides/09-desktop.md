---
layout: section
sectionNumber: '09'
---

# Docker Desktop и альтернативы

---
layout: compare
---

# Сравнение

<div class="comparison-grid">
  <CompareCard title="Docker Desktop" :items="['Compose, Scout, Extensions', 'Resource Saver', 'Коммерческая лицензия']" />
  <CompareCard title="OrbStack" :items="['Лёгкая (macOS only)', 'Быстрее, меньше ресурсов', 'Linux VM из коробки']" />
  <CompareCard title="Podman Desktop" :items="['Open-source', 'Rootless, daemonless', 'Docker-совместимый']" />
  <CompareCard title="Colima" :items="['CLI-ориентированный', 'macOS + Linux', 'Бесплатный, минималистичный']" />
</div>

---
layout: code-block
---

# GPU в Docker Compose

```yaml
services:
  ollama:
    image: ollama/ollama
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: 1
              capabilities: [gpu]
```

`deploy:` — синтаксис из Swarm, но GPU-резервация работает и в обычном `docker compose up`

AI/ML рядом с PHP: локальный inference, генерация embeddings, OCR с GPU-ускорением

---
layout: compare
---

# Docker & AI — новые инструменты

<div class="comparison-grid">
  <CompareCard title="Gordon (AI Agent)" :items="['Встроен в Docker Desktop', 'Генерирует Dockerfile', 'Отлаживает ошибки сборки', 'Объясняет проблемы']" />
  <CompareCard title="MCP Toolkit" :items="['Каталог MCP-серверов', 'Запуск в изоляции (контейнеры)', 'Подключение AI-агентов к БД, API', 'Model Context Protocol — стандарт Anthropic']" />
</div>
