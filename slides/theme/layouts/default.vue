<script setup>
import PageNumber from '../components/PageNumber.vue'

defineProps({
  class: { type: String, default: '' }
})
</script>

<template>
  <div class="slidev-layout default-layout" :class="$props.class">
    <slot />
    <PageNumber />
  </div>
</template>

<style scoped>
.default-layout {
  height: 100%;
}
</style>
