<script setup>
import { useI18n } from 'vue-i18n'

// What live shows while part of a page loads (the Blade views'
// .vue-placeholder): "Loading..." in light grey, pulsing, on the page
// background, keeping roughly the room the content will take.
defineProps({
  height: {
    type: String,
    default: null,
  },
})

const { t } = useI18n()
</script>

<template>
  <div class="vue-placeholder" :style="height ? { height } : null" role="status" data-testid="loading-placeholder">
    <div class="vue-placeholder-content">{{ t('partials.loading') }}...</div>
  </div>
</template>

<style scoped>
.vue-placeholder {
  display: grid;
  grid-template-columns: 1fr;
  align-items: center;
  justify-items: center;
  color: lightgrey;
  animation: vue-placeholder-pulsate 1s ease-out infinite;
  opacity: 0.5;
}

@keyframes vue-placeholder-pulsate {
  0% {
    opacity: 0.25;
  }

  50% {
    opacity: 1;
  }

  100% {
    opacity: 0.25;
  }
}
</style>
