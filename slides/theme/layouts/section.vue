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
  background: var(--purple, #6f02cd);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  position: relative;
  overflow: hidden;
  height: 100%;
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
  font-family: 'Unbounded', sans-serif;
  font-size: 8rem;
  font-weight: 900;
  opacity: 0.2;
  line-height: 1;
}

.content {
  z-index: 1;
}

.content :deep(h1) {
  font-family: 'Unbounded', sans-serif;
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
