---
layout: section
sectionNumber: '08'
---

# CI/CD и тестирование

---

# Генерация PDF и браузерное тестирование

<div class="accent-line"></div>

<div class="two-col-custom">
<div>

### PDF

- **Gotenberg 8** — Chromium + LibreOffice, REST API
- Замена wkhtmltopdf (deprecated)

</div>
<div>

### Браузерные тесты

- **Selenium** — standalone-chrome
- **Playwright** — mcr.microsoft.com
- **Browserless** — headless Chrome API

</div>
</div>

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
