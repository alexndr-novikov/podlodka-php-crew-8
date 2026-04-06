---
layout: section
sectionNumber: '07'
---

# Observability Stack

---

# OpenTelemetry

<div class="accent-line"></div>

- PHP SDK: `open-telemetry/sdk`, авто-инструментирование
- OTel Collector: `otel/opentelemetry-collector-contrib`
- Traces + Metrics + Logs — единый стандарт
- Vendor-agnostic: один SDK, любой бэкенд

---
layout: compare
alt: true
---

# Трейсинг

<div class="comparison-grid">
  <CompareCard title="Jaeger" description="jaegertracing/all-in-one — Визуализация трейсов" />
  <CompareCard title="Zipkin" description="openzipkin/zipkin — Альтернатива" />
  <CompareCard title="Grafana Tempo" description="grafana/tempo — Хранение для Grafana" />
</div>

---

# Логирование и All-in-one

<div class="accent-line"></div>

- **Grafana Loki** — агрегация логов, label-based
- Docker logging driver → Loki
- **Grafana** — единый UI для логов, метрик, трейсов

<CompareCard title="LGTM Stack" description="grafana/otel-lgtm — Loki + Grafana + Tempo + Mimir в одном контейнере для локальной разработки" :teal="true" />
