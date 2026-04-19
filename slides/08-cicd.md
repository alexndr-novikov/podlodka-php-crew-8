---
layout: section
sectionNumber: '08'
---

# Тестирование

---

# Браузерное тестирование

<div class="accent-line"></div>

- **Selenium** — standalone-chrome
- **Playwright** — mcr.microsoft.com
- **Browserless** — headless Chrome API

---

# Testcontainers для PHP

<div class="accent-line"></div>

- Интеграционные тесты с **реальными контейнерами** прямо из PHPUnit
- `testcontainers-php` — поднимает PostgreSQL, Redis, Meilisearch на лету
- Каждый тест получает чистый контейнер — нет shared state
- Замена моков: тестируем с настоящей БД, а не с фейковой
- Тренд 2025–2026: Testcontainers стал стандартом для integration tests

---
layout: compare
alt: true
---

# Аудит и безопасность образов

<div class="comparison-grid">
  <CompareCard title="Hadolint" description="Линтер Dockerfile" />
  <CompareCard title="Dockle" description="Аудит безопасности образов" />
  <CompareCard title="Trivy" description="Сканер уязвимостей (CVE)" />
  <CompareCard title="Docker Scout" description="Встроенный SBOM + CVE анализ" />
</div>
