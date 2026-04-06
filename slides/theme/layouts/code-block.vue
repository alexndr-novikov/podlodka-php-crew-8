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

.content :deep(code) {
  background: rgba(255,255,255,0.1);
  color: var(--teal, #00d4aa);
}

.content :deep(.shiki),
.content :deep(pre) {
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
