<script setup>
import PageNumber from '../components/PageNumber.vue'
</script>

<template>
  <div class="slidev-layout two-col-cards-layout" :class="{ alt: $attrs.alt }">
    <div class="accent-line"></div>
    <slot />
    <PageNumber />
  </div>
</template>

<style scoped>
.two-col-cards-layout {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem 4rem;
  height: 100%;
}

.two-col-cards-layout.alt {
  background: var(--bg-secondary, #faf7ff);
}

.two-col-cards-layout :deep(h1) {
  font-family: 'Unbounded', sans-serif;
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
