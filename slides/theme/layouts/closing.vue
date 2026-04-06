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
  height: 100%;
}

.closing-layout::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 50%, rgba(111, 2, 205, 0.3) 0%, transparent 60%);
  pointer-events: none;
}

.content { z-index: 1; }

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
