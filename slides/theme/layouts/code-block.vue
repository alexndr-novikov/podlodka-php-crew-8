<script setup>
import PageNumber from '../components/PageNumber.vue'
</script>

<template>
  <div class="slidev-layout code-layout">
    <div class="pattern-overlay"></div>
    <div class="content">
      <slot />
    </div>
    <PageNumber :light="true" />
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
  height: 100%;
}

.content { z-index: 1; }

.content :deep(h1),
.content :deep(h2) {
  font-family: 'Unbounded', sans-serif;
  color: var(--teal, #00d4aa);
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.content :deep(:not(pre) > code) {
  background: rgba(255,255,255,0.1);
  color: var(--teal, #00d4aa);
}

.content :deep(pre code) {
  background: transparent !important;
}

.content :deep(.slidev-code-wrapper) {
  background: #0d0b1a !important;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border-left: 3px solid var(--teal, #00d4aa);
}

.content :deep(pre),
.content :deep(.slidev-code) {
  background: transparent !important;
  border: none !important;
  padding: 0;
}

.content :deep(.shiki),
.content :deep(.shiki code),
.content :deep(.shiki span) {
  font-family: Consolas, 'JetBrains Mono', monospace !important;
  font-size: 0.95rem !important;
}

.content :deep(.shiki) {
  background: transparent !important;
}

.content :deep(.shiki .line),
.content :deep(.shiki .line span) {
  background: transparent !important;
  background-color: transparent !important;
}

.content :deep(.shiki span) {
  color: #f0eff4 !important;
}

.content :deep(.shiki .line > span:first-child) {
  color: #7dd3fc !important;
}

.content :deep(.shiki .line span[style*="666666"]),
.content :deep(.shiki .line span[style*="758575"]) {
  color: #94a3b8 !important;
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
