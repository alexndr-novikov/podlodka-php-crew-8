# Slidev Presentation Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the 55-slide presentation from a monolithic `presentation.html` to a Slidev project with Markdown source files split by section, custom Podlodka theme, and reusable Vue components.

**Architecture:** Slidev project in `slides/` directory. Source content split into 12 section files + entry point. Custom local theme with Podlodka branding. Vue components for comparison grids and cards. Build via `npx slidev build`, dev via `npx slidev`.

**Tech Stack:** Slidev v52+, Vue 3, UnoCSS, Node.js

---

### File Structure

```
slides/
  package.json              # Slidev + dependencies
  slides.md                 # Entry point: global config + title + agenda slides
  01-evolution.md           # Section 1: Эволюция Docker Compose (slides 3-9)
  02-dockerfile.md          # Section 2: Современный Dockerfile (slides 10-15)
  03-runtimes.md            # Section 3: PHP-рантаймы (slides 16-20)
  04-infrastructure.md      # Section 4: Контейнеры для инфраструктуры (slides 21-28)
  05-development.md         # Section 5: Разработка и отладка (slides 29-32)
  06-background.md          # Section 6: Фоновые задачи (slides 33-35)
  07-observability.md       # Section 7: Observability Stack (slides 36-39)
  08-cicd.md                # Section 8: CI/CD (slides 40-42)
  09-desktop.md             # Section 9: Docker Desktop (slides 43-44)
  10-references.md          # Section 10: Референсные реализации (slides 45-48)
  11-bestpractices.md       # Section 11: Best Practices 2026 (slides 49-52)
  12-workshop.md            # Section 12: Структура воркшопа + closing (slides 53-55)
  theme/
    index.ts                # Theme entry point
    styles/
      base.css              # Reset, typography, brand colors
      layouts.css           # Layout-specific styles
      code.css              # Code block theming
    layouts/
      section.vue           # Section divider (purple bg, large number)
      code-block.vue        # Dark code slide
      compare.vue           # Slide with comparison grid
      two-col-cards.vue     # Two-column card layout
      closing.vue           # Closing slide
    components/
      CompareCard.vue       # Reusable comparison card
  .gitignore                # node_modules, dist
```

---

### Task 1: Initialize Slidev project

**Files:**
- Create: `slides/package.json`
- Create: `slides/.gitignore`
- Create: `slides/.npmrc`

- [ ] **Step 1: Create package.json**

```json
{
  "name": "podlodka-php-crew-8-slides",
  "private": true,
  "scripts": {
    "dev": "slidev",
    "build": "slidev build",
    "export": "slidev export"
  },
  "dependencies": {
    "@slidev/cli": "^52.0.0",
    "@slidev/theme-default": "latest"
  }
}
```

- [ ] **Step 2: Create .gitignore**

```
node_modules/
dist/
.slidev/
```

- [ ] **Step 3: Create .npmrc to avoid hoisting issues**

```
shamefully-hoist=true
```

- [ ] **Step 4: Install dependencies**

Run: `cd slides && npm install`
Expected: `node_modules/` created, no errors

- [ ] **Step 5: Create minimal slides.md to verify Slidev works**

```markdown
---
theme: ./theme
title: "Docker для PHP-разработчика в 2026"
info: "Podlodka PHP Crew 8"
fonts:
  sans: Lexend
  serif: Lexend
  mono: JetBrains Mono
  local: Unbounded, Lexend, JetBrains Mono
  provider: google
---

# Docker для PHP‑разработчика в 2026

Локальное окружение, актуальные практики, современные контейнеры

<div class="abs-bl m-6 text-sm opacity-50">
Podlodka PHP Crew 8
</div>
```

- [ ] **Step 6: Create minimal theme entry point so Slidev can start**

Create `slides/theme/index.ts`:
```ts
import { defineTheme } from '@slidev/types'

export default defineTheme({
  name: 'podlodka',
})
```

Create `slides/theme/styles/base.css`:
```css
:root {
  --purple: #6f02cd;
  --purple-deep: #4a0187;
  --purple-dark: #1a0033;
  --purple-light: #f3e8ff;
  --teal: #00d4aa;
  --teal-light: #e0fff7;
}
```

Create `slides/theme/package.json`:
```json
{
  "name": "@podlodka/slidev-theme",
  "engines": {
    "slidev": ">=0.48.0"
  },
  "slidev": {
    "colorSchema": "light",
    "defaults": {
      "fonts": {
        "sans": "Lexend",
        "mono": "JetBrains Mono",
        "local": "Unbounded, Lexend, JetBrains Mono",
        "provider": "google"
      }
    }
  }
}
```

- [ ] **Step 7: Verify Slidev starts**

Run: `cd slides && npx slidev --port 3030`
Expected: Dev server starts at `http://localhost:3030`, shows title slide

- [ ] **Step 8: Commit**

```bash
git add slides/package.json slides/.gitignore slides/.npmrc slides/slides.md slides/theme/
git commit -m "Init Slidev project with Podlodka theme skeleton"
```

---

### Task 2: Build the custom Podlodka theme — styles

**Files:**
- Create: `slides/theme/styles/base.css`
- Create: `slides/theme/styles/layouts.css`
- Create: `slides/theme/styles/code.css`
- Modify: `slides/theme/index.ts`

- [ ] **Step 1: Write base.css — brand colors, typography, global styles**

Port CSS custom properties and base styles from `presentation.html` lines 19-77. Key elements:
- `:root` variables for purple/teal brand colors
- Font families: Unbounded (display), Lexend (body), JetBrains Mono (code)
- Body background, text colors
- `.slidev-layout` base padding and font sizing

```css
:root {
  --purple: #6f02cd;
  --purple-deep: #4a0187;
  --purple-dark: #1a0033;
  --purple-light: #f3e8ff;
  --purple-glow: rgba(111, 2, 205, 0.15);
  --teal: #00d4aa;
  --teal-light: #e0fff7;

  --bg-primary: #ffffff;
  --bg-secondary: #faf7ff;
  --bg-dark: #110022;
  --bg-code: #1e1136;

  --text-primary: #1a0033;
  --text-secondary: #5c4a73;
  --text-on-dark: #f0e6ff;
  --text-muted: #9882b0;
}

.slidev-layout {
  font-family: 'Lexend', sans-serif;
  color: var(--text-primary);
}

.slidev-layout h1,
.slidev-layout h2,
.slidev-layout h3 {
  font-family: 'Unbounded', cursive;
}

.slidev-layout h2 {
  color: var(--purple);
  font-weight: 700;
}

.slidev-layout h3 {
  color: var(--purple-deep);
  font-weight: 600;
}

.slidev-layout ul li {
  color: var(--text-secondary);
  line-height: 1.5;
}

.slidev-layout code {
  font-family: 'JetBrains Mono', monospace;
  background: rgba(111, 2, 205, 0.08);
  padding: 0.1em 0.35em;
  border-radius: 3px;
  color: var(--purple-deep);
  font-size: 0.9em;
}

/* Tags / badges */
.tag {
  display: inline-block;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75em;
  background: rgba(111, 2, 205, 0.1);
  color: var(--purple);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
}

.tag-teal {
  background: rgba(0, 212, 170, 0.12);
  color: #008f6e;
}
```

- [ ] **Step 2: Write layouts.css — comparison grids, two-col, accent lines**

```css
/* Comparison grid */
.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
  gap: 1rem;
}

.compare-card {
  background: rgba(111, 2, 205, 0.06);
  border-radius: 0.75rem;
  padding: 1.25rem;
  border: 1px solid rgba(111, 2, 205, 0.12);
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}

.compare-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(111, 2, 205, 0.12);
}

.compare-card h3 {
  font-family: 'Unbounded', cursive;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--purple);
  margin-bottom: 0.5rem;
}

.compare-card p,
.compare-card li {
  font-size: 0.85rem;
  color: var(--text-secondary);
  line-height: 1.4;
}

.compare-card ul {
  list-style: none;
  padding: 0;
}

.compare-card ul li::before {
  content: '→ ';
  color: var(--teal);
  font-weight: 600;
}

/* Accent line above headings */
.accent-line {
  width: 4rem;
  height: 3px;
  background: linear-gradient(90deg, var(--purple), var(--teal));
  border-radius: 2px;
  margin-bottom: 1.5rem;
}

/* Two-column layout */
.two-col-custom {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  align-items: start;
}

/* Table styles */
.slide-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.slide-table th {
  font-family: 'Unbounded', cursive;
  font-weight: 600;
  text-align: left;
  padding: 0.5rem 0.75rem;
  background: rgba(111, 2, 205, 0.08);
  color: var(--purple);
  border-bottom: 2px solid var(--purple);
}

.slide-table td {
  padding: 0.4rem 0.75rem;
  border-bottom: 1px solid rgba(111, 2, 205, 0.08);
  color: var(--text-secondary);
}

.slide-table tr:last-child td {
  border-bottom: none;
}
```

- [ ] **Step 3: Write code.css — code block theme for dark slides**

```css
/* Dark code slides override */
.dark-code .slidev-layout {
  background: var(--bg-code);
  color: var(--text-on-dark);
}

.dark-code .slidev-layout h2 {
  color: var(--teal);
}

.dark-code .slidev-layout code {
  background: rgba(255, 255, 255, 0.1);
  color: var(--teal);
}

/* Syntax highlighting overrides for dark slides */
.dark-code .shiki {
  background: rgba(0, 0, 0, 0.3) !important;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border-left: 3px solid var(--purple);
}
```

- [ ] **Step 4: Import all styles in theme/index.ts**

```ts
import './styles/base.css'
import './styles/layouts.css'
import './styles/code.css'
```

- [ ] **Step 5: Verify theme loads — check that title slide shows branded typography**

Run: `cd slides && npx slidev --port 3030`
Expected: Title slide shows Unbounded font, purple/teal accent colors

- [ ] **Step 6: Commit**

```bash
git add slides/theme/styles/ slides/theme/index.ts
git commit -m "Add Podlodka theme styles: brand colors, layouts, code"
```

---

### Task 3: Build custom layouts

**Files:**
- Create: `slides/theme/layouts/section.vue`
- Create: `slides/theme/layouts/code-block.vue`
- Create: `slides/theme/layouts/compare.vue`
- Create: `slides/theme/layouts/two-col-cards.vue`
- Create: `slides/theme/layouts/closing.vue`

- [ ] **Step 1: Create section.vue — section divider layout**

Port from `presentation.html` `.section-divider` (lines 258-288). Purple background, large faded number, centered heading.

```vue
<template>
  <div class="slidev-layout section-layout">
    <div class="pattern-overlay"></div>
    <div class="section-number">{{ $attrs.sectionNumber || '01' }}</div>
    <div class="content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.section-layout {
  background: var(--purple);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.section-layout::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(0,212,170,0.15) 0%, transparent 50%);
  pointer-events: none;
}

.section-number {
  font-family: 'Unbounded', cursive;
  font-size: 8rem;
  font-weight: 900;
  opacity: 0.2;
  line-height: 1;
}

.content {
  z-index: 1;
}

.content :deep(h1) {
  font-family: 'Unbounded', cursive;
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin-top: 0.5rem;
}

.pattern-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}
</style>
```

- [ ] **Step 2: Create code-block.vue — dark-themed code slide**

```vue
<template>
  <div class="slidev-layout code-layout">
    <div class="pattern-overlay"></div>
    <div class="content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.code-layout {
  background: var(--bg-code, #1e1136);
  color: var(--text-on-dark, #f0e6ff);
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem 4rem;
  position: relative;
  overflow: hidden;
}

.content {
  z-index: 1;
}

.content :deep(h1),
.content :deep(h2) {
  font-family: 'Unbounded', cursive;
  color: var(--teal, #00d4aa);
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.content :deep(code) {
  background: rgba(255,255,255,0.1);
  color: var(--teal, #00d4aa);
}

.content :deep(.shiki) {
  background: rgba(0,0,0,0.3) !important;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border-left: 3px solid var(--purple, #6f02cd);
}

.pattern-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}
</style>
```

- [ ] **Step 3: Create compare.vue — comparison grid layout**

```vue
<template>
  <div class="slidev-layout compare-layout" :class="{ alt: $attrs.alt }">
    <div class="accent-line"></div>
    <slot />
  </div>
</template>

<style scoped>
.compare-layout {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem 4rem;
}

.compare-layout.alt {
  background: var(--bg-secondary, #faf7ff);
}

.compare-layout :deep(h1) {
  font-family: 'Unbounded', cursive;
  color: var(--purple);
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.accent-line {
  width: 4rem;
  height: 3px;
  background: linear-gradient(90deg, var(--purple), var(--teal));
  border-radius: 2px;
  margin-bottom: 1.5rem;
}
</style>
```

- [ ] **Step 4: Create two-col-cards.vue**

```vue
<template>
  <div class="slidev-layout two-col-cards-layout" :class="{ alt: $attrs.alt }">
    <div class="accent-line"></div>
    <slot />
  </div>
</template>

<style scoped>
.two-col-cards-layout {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem 4rem;
}

.two-col-cards-layout.alt {
  background: var(--bg-secondary, #faf7ff);
}

.two-col-cards-layout :deep(h1) {
  font-family: 'Unbounded', cursive;
  color: var(--purple);
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.accent-line {
  width: 4rem;
  height: 3px;
  background: linear-gradient(90deg, var(--purple), var(--teal));
  border-radius: 2px;
  margin-bottom: 1.5rem;
}
</style>
```

- [ ] **Step 5: Create closing.vue**

```vue
<template>
  <div class="slidev-layout closing-layout">
    <div class="pattern-overlay"></div>
    <div class="content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.closing-layout {
  background: var(--bg-dark, #110022);
  color: var(--text-on-dark, #f0e6ff);
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.closing-layout::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 50%, rgba(111, 2, 205, 0.3) 0%, transparent 60%);
  pointer-events: none;
}

.content {
  z-index: 1;
}

.content :deep(h1) {
  font-family: 'Unbounded', cursive;
  font-size: 4rem;
  font-weight: 800;
  color: var(--text-on-dark);
}

.content :deep(p) {
  color: var(--teal);
  margin-top: 0.75rem;
}

.pattern-overlay {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}
</style>
```

- [ ] **Step 6: Verify layouts render — add test slides using each layout**

Temporarily add to `slides.md`:
```markdown
---
layout: section
sectionNumber: '01'
---

# Test Section

---
layout: code-block
---

# Code Test

\`\`\`yaml
services:
  php:
    image: php:8.4
\`\`\`

---
layout: closing
---

# Спасибо!

Вопросы?
```

Run: `cd slides && npx slidev --port 3030`
Expected: Section slide shows purple bg with "01", code slide has dark bg, closing has dark bg with radial glow

- [ ] **Step 7: Commit**

```bash
git add slides/theme/layouts/
git commit -m "Add custom Slidev layouts: section, code-block, compare, two-col-cards, closing"
```

---

### Task 4: Create CompareCard component

**Files:**
- Create: `slides/theme/components/CompareCard.vue`

- [ ] **Step 1: Create CompareCard.vue**

```vue
<script setup>
defineProps({
  title: { type: String, required: true },
  items: { type: Array, default: () => [] },
  description: { type: String, default: '' },
  teal: { type: Boolean, default: false },
})
</script>

<template>
  <div class="compare-card" :class="{ 'compare-card--teal': teal }">
    <h3>{{ title }}</h3>
    <p v-if="description">{{ description }}</p>
    <ul v-if="items.length">
      <li v-for="item in items" :key="item">{{ item }}</li>
    </ul>
    <slot />
  </div>
</template>

<style scoped>
.compare-card {
  background: rgba(111, 2, 205, 0.06);
  border-radius: 0.75rem;
  padding: 1.25rem;
  border: 1px solid rgba(111, 2, 205, 0.12);
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}

.compare-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(111, 2, 205, 0.12);
}

.compare-card--teal {
  background: rgba(0, 212, 170, 0.08);
  border-color: rgba(0, 212, 170, 0.2);
}

.compare-card--teal h3 {
  color: #008f6e;
}

.compare-card h3 {
  font-family: 'Unbounded', cursive;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--purple, #6f02cd);
  margin-bottom: 0.5rem;
}

.compare-card p {
  font-size: 0.85rem;
  color: var(--text-secondary, #5c4a73);
  line-height: 1.4;
}

.compare-card ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.compare-card ul li {
  font-size: 0.85rem;
  color: var(--text-secondary, #5c4a73);
  line-height: 1.4;
}

.compare-card ul li::before {
  content: '→ ';
  color: var(--teal, #00d4aa);
  font-weight: 600;
}
</style>
```

- [ ] **Step 2: Verify component renders in a test slide**

Add to `slides.md`:
```markdown
---
layout: compare
---

# Test Compare

<div class="comparison-grid">
  <CompareCard title="Card A" :items="['Item 1', 'Item 2']" />
  <CompareCard title="Card B" description="A description" />
</div>
```

Run: `cd slides && npx slidev --port 3030`
Expected: Two cards render side-by-side with purple styling

- [ ] **Step 3: Commit**

```bash
git add slides/theme/components/CompareCard.vue
git commit -m "Add CompareCard reusable component"
```

---

### Task 5: Migrate slides — entry point + sections 1-4

**Files:**
- Modify: `slides/slides.md` (title + agenda, then `src:` imports)
- Create: `slides/01-evolution.md`
- Create: `slides/02-dockerfile.md`
- Create: `slides/03-runtimes.md`
- Create: `slides/04-infrastructure.md`

- [ ] **Step 1: Write slides.md — title slide, agenda, section imports**

Replace the test content in `slides.md` with the full entry point:

```markdown
---
theme: ./theme
title: "Docker для PHP-разработчика в 2026"
info: "Podlodka PHP Crew 8"
fonts:
  sans: Lexend
  serif: Lexend
  mono: JetBrains Mono
  local: Unbounded, Lexend, JetBrains Mono
  provider: google
drawings:
  persist: false
transition: slide-left
---

# Docker для PHP‑разработчика в 2026

Локальное окружение, актуальные практики, современные контейнеры

<div class="abs-bl m-6 text-sm opacity-50">
Podlodka PHP Crew 8
</div>

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
```

- [ ] **Step 2: Write 01-evolution.md**

Migrate slides 3-9 from `presentation.html`:

```markdown
---
layout: section
sectionNumber: '01'
---

# Эволюция Docker Compose

---

# Формат и CLI

<div class="accent-line"></div>

- `compose.yml` вместо `docker-compose.yml` — новое каноничное имя
- `docker compose` (встроенная команда) вместо `docker-compose` (deprecated)
- Compose V2 — Go-реализация, плагин Docker CLI
- `docker compose watch` — встроенный file-watching
- `docker compose up --wait` — ожидание healthcheck
- `docker compose alpha dry-run` — предпросмотр без запуска

---
layout: default
class: bg-purple-50/30
---

# Новый синтаксис compose.yml

<div class="accent-line"></div>

- `develop.watch` — нативная конфигурация hot-reload
- `include` — подключение внешних compose-файлов
- `configs` и `secrets` на уровне top-level (без Swarm)
- `profiles` для группировки сервисов (dev, test, debug)
- `depends_on` с `condition: service_healthy`
- Удаление `version:` — поле больше не нужно

---
layout: code-block
---

# develop.watch — конфигурация

```yaml
services:
  php:
    develop:
      watch:
        - action: sync       # src/ → контейнер
          path: ./src
          target: /app/src
        - action: rebuild     # composer.json → пересборка
          path: ./composer.json
        - action: restart     # .env → перезапуск
          path: ./.env
      ignore:
        - vendor/
        - node_modules/
```

---
layout: compare
---

# watch vs bind mount

<div class="comparison-grid">
  <CompareCard title="Bind Mount" :items="['Linux — нет overhead', 'Большие проекты', 'Двусторонняя синхронизация', 'vendor, генерируемые файлы']" />
  <CompareCard title="Watch Sync" :items="['macOS/Windows — быстрее', 'Конкретные пути', 'Односторонний поток', 'src/, app/, templates/']" />
  <CompareCard title="Watch Rebuild" :items="['composer.json', 'package.json', 'Dockerfile']" />
  <CompareCard title="Watch Restart" :items="['.env файлы', '.rr.yaml', 'supervisord.conf']" />
</div>

---
layout: compare
alt: true
---

# watch: различия по ОС

<div class="comparison-grid">
  <CompareCard title="🍎 macOS" :items="['fsevents API — нативный', 'Регрессия в v5.0.1 (too many open files)', 'Всегда заполняйте ignore', 'VirtioFS overhead → sync может быть быстрее']" />
  <CompareCard title="🐧 Linux" :items="['inotify — лимит watchers', 'Лимит общий для всех контейнеров + хост', 'sysctl max_user_watches=524288', 'Bind mount нативно без overhead']" />
  <CompareCard title="🪟 Windows (WSL2)" :items="['Файлы в WSL2 FS, не /mnt/c/', 'CIFS не поддерживает inotify', 'VS Code Remote WSL']" />
</div>

---

# Environments & .env

<div class="accent-line"></div>

- Приоритет `.env` файлов: `env_file: [.env, .env.local]`
- `COMPOSE_PROJECT_NAME` и `COMPOSE_PROFILES` в .env
- `COMPOSE_FILE` с несколькими файлами для overlay-конфигурации
- Интерполяция: `${VAR:-default}`, `${VAR:?error}`
- Переход от `environment:` к `env_file:` для чистоты
```

- [ ] **Step 3: Write 02-dockerfile.md**

Migrate slides 10-15:

```markdown
---
layout: section
sectionNumber: '02'
---

# Современный Dockerfile для PHP

---
layout: default
class: bg-purple-50/30
---

# Базовые образы

<div class="accent-line"></div>

- Официальные: `php:8.3-fpm`, `php:8.4-fpm`, `php:8.4-cli`
- Фиксация версий: `php:8.4.2-fpm-bookworm` вместо `php:8.4-fpm`
- `FROM --platform=$BUILDPLATFORM` для multi-arch сборки
- Alpine ~50 MB vs Debian ~250 MB, но разница сокращается после расширений

---

# Alpine vs Debian: подводные камни

<div class="accent-line"></div>

- **musl vs glibc** — корень большинства проблем
- **Imagick:** отсутствие шрифтов, ICC-профилей, HEIC/AVIF delegates
- **DNS:** musl не поддерживает search/ndots как glibc
- **iconv:** урезанная реализация, проблемы с CP1251, KOI8-R
- **malloc:** musl медленнее под нагрузкой (решение: jemalloc)
- **PECL:** grpc, protobuf могут не компилироваться

---
layout: code-block
---

# BuildKit фичи

```dockerfile
# Кэш Composer между сборками
RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev

# Безопасная передача токенов
RUN --mount=type=secret,id=composer_auth \
    composer config -g github-oauth ...

# Копирование без инвалидации слоёв
COPY --link --chmod=755 . /app

# Heredoc — многострочные скрипты
RUN <<EOF
  apt-get update && apt-get install -y libpq-dev
  docker-php-ext-install pdo_pgsql
EOF
```

---
layout: default
class: bg-purple-50/30
---

# PHP-расширения

<div class="accent-line"></div>

- `docker-php-extension-installer` (mlocati) — де-факто стандарт
- Типичный набор: <span class="tag">pdo_pgsql</span> <span class="tag">redis</span> <span class="tag">intl</span> <span class="tag">gd</span> <span class="tag">zip</span> <span class="tag">opcache</span>
- Для разработки: <span class="tag tag-teal">xdebug</span> <span class="tag tag-teal">pcov</span> <span class="tag tag-teal">excimer</span>
- Разделение dev/prod через multi-stage targets

---

# Безопасность

<div class="accent-line"></div>

- Запуск от не-root пользователя — `USER www-data`
- Синхронизация UID/GID через build args
- Минимизация attack surface: удаление dev-пакетов в production
- Multi-stage: `--target dev` / `--target prod` / `--target test`
```

- [ ] **Step 4: Write 03-runtimes.md**

Migrate slides 16-20:

```markdown
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
```

- [ ] **Step 5: Write 04-infrastructure.md**

Migrate slides 21-28:

```markdown
---
layout: section
sectionNumber: '04'
---

# Контейнеры для инфраструктуры

---
layout: compare
---

# Базы данных

<div class="comparison-grid">
  <CompareCard title="PostgreSQL 17" :items="['Default в Laravel 11+', 'pg_isready healthcheck']" />
  <CompareCard title="MySQL 8.4 / 9.0" :items="['mysqladmin ping', 'Классический выбор']" />
  <CompareCard title="MariaDB 11" :items="['Open-source форк', 'Совместимость с MySQL']" />
</div>

- Named volumes для персистентности
- Инициализация через `/docker-entrypoint-initdb.d/`

---
layout: compare
alt: true
---

# Кэш: Redis и альтернативы

<div class="comparison-grid">
  <CompareCard title="Redis 7" :items="['redis-stack для RedisInsight', 'Классика, проверенный']" />
  <CompareCard title="Valkey 8" :items="['Fork Redis (open-source)', 'Drop-in замена']" />
  <CompareCard title="DragonflyDB" :items="['Высокопроизводительная', 'Redis-совместимая']" />
  <CompareCard title="KeyDB" :items="['Многопоточный', 'Redis-совместимый']" />
</div>

---
layout: compare
---

# Очереди

<div class="comparison-grid">
  <CompareCard title="RabbitMQ 4" :items="['management UI', 'AMQP стандарт']" />
  <CompareCard title="Apache Kafka" :items="['KRaft mode (без ZooKeeper)', 'bitnami/kafka']" />
  <CompareCard title="NATS 2" :items="['Легковесная', 'Pub/sub + очереди']" />
</div>

---
layout: compare
alt: true
---

# Поиск

<div class="comparison-grid">
  <CompareCard title="Meilisearch v1" description="Рекомендация Laravel Scout. Простой, быстрый" />
  <CompareCard title="Typesense 27" description="Альтернатива. Typo-tolerance из коробки" />
  <CompareCard title="Elasticsearch 8" description="Для сложных случаев. Full-text + аналитика" />
  <CompareCard title="Manticore" description="Лёгкая альтернатива. Бывший Sphinx" />
</div>

---

# Почта: Mailpit

<div class="accent-line"></div>

- `axllent/mailpit` — замена MailHog (unmaintained с 2022)
- Современный UI, HTML-preview, поддержка POP3
- SMTP на порту <span class="tag">1025</span>, веб-интерфейс на <span class="tag">8025</span>
- API для интеграционных тестов

---
layout: default
class: bg-purple-50/30
---

# S3: LocalStack vs MinIO

<div class="accent-line"></div>

<div class="two-col-custom">
  <CompareCard title="LocalStack" :items="['S3, SQS, SNS, DynamoDB, Lambda', 'Полная эмуляция AWS', 'Community edition бесплатен']" />
  <CompareCard title="MinIO" :items="['Чистый S3-совместимый', 'Проще в настройке', 'Легче по ресурсам']" />
</div>

Только S3 → MinIO. Нужны SQS/SNS/Lambda → LocalStack.

---
layout: compare
---

# Reverse Proxy

<div class="comparison-grid">
  <CompareCard title="Caddy 2" :items="['Автоматический HTTPS', 'Простая конфигурация']" />
  <CompareCard title="Traefik v3" :items="['Service discovery по Docker labels', 'Автоматический роутинг']" />
  <CompareCard title="Nginx Proxy Manager" :items="['UI для управления', 'Знакомый nginx']" />
</div>

Локальные домены: `*.localhost`, `*.test` + `mkcert` для HTTPS
```

- [ ] **Step 6: Verify slides 1-28 render correctly**

Run: `cd slides && npx slidev --port 3030`
Expected: Navigate through first ~28 slides. Section dividers show purple bg + number. Code slides have dark theme. Compare slides show card grids.

- [ ] **Step 7: Commit**

```bash
git add slides/slides.md slides/01-evolution.md slides/02-dockerfile.md slides/03-runtimes.md slides/04-infrastructure.md
git commit -m "Migrate slides 1-28: title, agenda, sections 1-4"
```

---

### Task 6: Migrate slides — sections 5-8

**Files:**
- Create: `slides/05-development.md`
- Create: `slides/06-background.md`
- Create: `slides/07-observability.md`
- Create: `slides/08-cicd.md`

- [ ] **Step 1: Write 05-development.md**

Migrate slides 29-32:

```markdown
---
layout: section
sectionNumber: '05'
---

# Разработка и отладка

---
layout: code-block
---

# Xdebug 3

```yaml
# Настройка через переменные окружения
environment:
  XDEBUG_MODE: debug       # debug | profile | trace | coverage
  XDEBUG_CONFIG: "client_host=host.docker.internal"

# Включение через profiles
services:
  php-debug:
    extends: php
    profiles: [debug]
    build:
      target: dev
```

---
layout: compare
---

# Профилирование и мониторинг

<div class="comparison-grid">
  <CompareCard title="Excimer" description="Low-overhead profiling. Используется Wikipedia" />
  <CompareCard title="SPX" description="Простой профилировщик с веб-UI" />
  <CompareCard title="Buggregator" description="All-in-one: Xdebug, VarDumper, Ray, SMTP, Sentry, Profiler" />
</div>

---
layout: default
class: bg-purple-50/30
---

# Инструменты качества кода

<div class="accent-line"></div>

- **PHPStan / Psalm** — статический анализ
- **PHP CS Fixer / PHP_CodeSniffer** — code style
- **Rector** — автоматический рефакторинг
- Запуск через `docker compose run` или как отдельные сервисы
```

- [ ] **Step 2: Write 06-background.md**

Migrate slides 33-35:

```markdown
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
```

- [ ] **Step 3: Write 07-observability.md**

Migrate slides 36-39:

```markdown
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
```

- [ ] **Step 4: Write 08-cicd.md**

Migrate slides 40-42:

```markdown
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
```

- [ ] **Step 5: Verify slides 29-42 render**

Run: `cd slides && npx slidev --port 3030`
Expected: All sections 5-8 render with correct layouts

- [ ] **Step 6: Commit**

```bash
git add slides/05-development.md slides/06-background.md slides/07-observability.md slides/08-cicd.md
git commit -m "Migrate slides 29-42: sections 5-8"
```

---

### Task 7: Migrate slides — sections 9-12 + closing

**Files:**
- Create: `slides/09-desktop.md`
- Create: `slides/10-references.md`
- Create: `slides/11-bestpractices.md`
- Create: `slides/12-workshop.md`

- [ ] **Step 1: Write 09-desktop.md**

Migrate slides 43-44:

```markdown
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
```

- [ ] **Step 2: Write 10-references.md**

Migrate slides 45-48:

```markdown
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
```

- [ ] **Step 3: Write 11-bestpractices.md**

Migrate slides 49-52:

```markdown
---
layout: section
sectionNumber: '11'
---

# Best Practices 2026

---

# Архитектура и конфигурация

<div class="accent-line"></div>

- Один процесс — один контейнер
- Healthcheck для каждого сервиса
- Named volumes для данных, bind mounts для кода
- `.dockerignore` — обязательно
- Multi-stage builds: `dev → test → production`
- Pinning версий образов до минорной версии

---
layout: default
class: bg-purple-50/30
---

# Workflow и инструменты

<div class="accent-line"></div>

- `develop.watch` вместо сторонних file-watchers
- `profiles` для опциональных сервисов
- Compose `include` для модульности
- Кэширование зависимостей через `--mount=type=cache`
- Non-root user в контейнерах
- `.env` + `.env.local` для конфигурации

---
layout: code-block
---

# Makefile / Taskfile / Just

```makefile
# Makefile — алиасы для частых команд
up:
	docker compose up -d --wait

down:
	docker compose down

shell:
	docker compose exec php sh

test:
	docker compose run --rm php vendor/bin/phpunit

lint:
	docker compose run --rm php vendor/bin/phpstan analyse
```
```

- [ ] **Step 4: Write 12-workshop.md**

Migrate slides 53-55:

```markdown
---
layout: section
sectionNumber: '12'
---

# Структура воркшопа

---

# Что будем делать

<div class="accent-line"></div>

- **Before & After** — docker-compose.yml (2021) → compose.yml (2026)
- **Live coding** — PHP 8.4 + PostgreSQL + Valkey + Mailpit + Meilisearch
- **Dockerfile** — production-ready с BuildKit фичами
- **Runtime showdown** — PHP-FPM vs FrankenPHP vs RoadRunner
- **Observability** — OpenTelemetry + Grafana LGTM

---
layout: closing
---

# Спасибо!

Вопросы и обсуждение

<div class="mt-8 text-sm opacity-50">
Podlodka PHP Crew 8
</div>
```

- [ ] **Step 5: Verify all 55 slides render end-to-end**

Run: `cd slides && npx slidev --port 3030`
Expected: Navigate through all slides. Total count should be 55. All layouts render correctly.

- [ ] **Step 6: Commit**

```bash
git add slides/09-desktop.md slides/10-references.md slides/11-bestpractices.md slides/12-workshop.md
git commit -m "Migrate slides 43-55: sections 9-12 + closing"
```

---

### Task 8: Add Makefile targets and increase font sizes

**Files:**
- Modify: `Makefile`
- Modify: `slides/theme/styles/base.css` (increase font sizes)

- [ ] **Step 1: Add slides targets to Makefile**

Read existing Makefile to find the right place to add targets, then append:

```makefile
## — Slides ————————————————————————————————————————————
slides-dev: ## Start Slidev dev server
	cd slides && npx slidev --port 3030 --open

slides-build: ## Build slides for deployment
	cd slides && npx slidev build

slides-export: ## Export slides to PDF
	cd slides && npx slidev export
```

- [ ] **Step 2: Increase font sizes in base.css**

Update the CSS in `slides/theme/styles/base.css` to use larger fonts (the original request that started this redesign):

```css
.slidev-layout h1 {
  font-size: 2.8rem;
}

.slidev-layout h2 {
  font-size: 2.2rem;
}

.slidev-layout h3 {
  font-size: 1.5rem;
}

.slidev-layout ul li,
.slidev-layout p {
  font-size: 1.25rem;
}
```

- [ ] **Step 3: Verify font sizes look good**

Run: `cd slides && npx slidev --port 3030`
Expected: Text is noticeably larger and readable from a distance (conference presentation)

- [ ] **Step 4: Commit**

```bash
git add Makefile slides/theme/styles/base.css
git commit -m "Add Makefile targets for slides, increase font sizes"
```

---

### Task 9: Final verification and cleanup

**Files:**
- No new files

- [ ] **Step 1: Run full slide deck end-to-end**

Run: `cd slides && npx slidev --port 3030`
Expected: All 55 slides render. Navigate with arrow keys. Code blocks have syntax highlighting. CompareCard components render in grids.

- [ ] **Step 2: Test build**

Run: `cd slides && npx slidev build`
Expected: `dist/` folder created with static SPA

- [ ] **Step 3: Verify old presentation.html is still present (don't delete yet)**

The user may want to compare before removing it. Leave `presentation.html` in place.

- [ ] **Step 4: Final commit if any adjustments were needed**

```bash
git add -A slides/
git commit -m "Finalize Slidev migration: all 55 slides migrated"
```
