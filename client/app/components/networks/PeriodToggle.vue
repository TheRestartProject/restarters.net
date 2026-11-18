<script setup>
import { useI18n } from 'vue-i18n'

// "All time" / "Last 12 months" switch for headline stats, used with v-model.
// The value is the `period` name that the stats API takes. Last 12 months
// means the last 12 completed calendar months.
defineProps({
  modelValue: {
    type: String,
    default: 'all',
  },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
</script>

<template>
  <div class="btn-group period-toggle" role="group" :aria-label="t('networks.stats.period_toggle')" data-testid="period-toggle">
    <button
      type="button"
      class="btn btn-sm"
      :class="{ active: modelValue === 'all' }"
      :aria-pressed="modelValue === 'all'"
      data-testid="period-all"
      @click="emit('update:modelValue', 'all')"
    >
      {{ t('networks.stats.all_time') }}
    </button>
    <button
      type="button"
      class="btn btn-sm"
      :class="{ active: modelValue === 'last_12_months' }"
      :aria-pressed="modelValue === 'last_12_months'"
      data-testid="period-last12"
      @click="emit('update:modelValue', 'last_12_months')"
    >
      {{ t('networks.stats.last_12_months') }}
    </button>
  </div>
</template>

<style scoped lang="scss">
// Black-on-white, inverted when active, as the other toggles on the site.
.btn {
  color: #000;
  background-color: #fff;
  border: 1px solid #000;

  &.active {
    color: #fff;
    background-color: #000;
  }
}
</style>
