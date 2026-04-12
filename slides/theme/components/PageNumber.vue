<script setup>
defineProps({
  light: { type: Boolean, default: false }
})
</script>

<template>
  <div class="page-number" :class="{ 'page-number--light': light }">
    {{ $slidev.nav.currentSlideNo }} / {{ $slidev.nav.total }}
  </div>
</template>

<style scoped>
.page-number {
  position: absolute;
  bottom: 1rem;
  right: 1.5rem;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75rem;
  color: var(--text-muted, #9882b0);
  opacity: 0.5;
  z-index: 10;
}

.page-number--light {
  color: rgba(255, 255, 255, 0.4);
}
</style>
