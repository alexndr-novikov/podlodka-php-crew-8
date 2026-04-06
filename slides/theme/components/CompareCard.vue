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
