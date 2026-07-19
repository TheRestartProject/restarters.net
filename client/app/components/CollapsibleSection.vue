<script setup>
import { ref } from 'vue'

// Mobile-collapsible section (legacy resources/js/components/CollapsibleSection.vue):
// on desktop the content is always shown; on mobile the heading carries a "+"/"−"
// toggle that collapses the body. Used by the dashboard section cards, whose
// headings were collapsible on mobile in the legacy app.
const props = defineProps({
  // Start collapsed on mobile (desktop is always expanded regardless).
  collapsedOnMobile: { type: Boolean, default: false },
})

const expanded = ref(!props.collapsedOnMobile)

function toggle() {
  expanded.value = !expanded.value
}
</script>

<template>
  <div class="collapsible-section">
    <div
      class="collapsible-section__header d-flex align-items-center"
      data-testid="collapsible-header"
      @click="toggle"
    >
      <!-- flex-grow-1 lets a title that has its own justify-between layout
           (e.g. Your Groups' heading + "newly added" badge) span the row. -->
      <div class="flex-grow-1">
        <slot name="title" />
      </div>
      <!-- Mobile-only expand/collapse affordance; desktop keeps the body open. -->
      <span
        class="collapsible-section__toggle d-md-none"
        :aria-expanded="expanded"
        data-testid="collapsible-toggle"
      >{{ expanded ? '−' : '+' }}</span>
    </div>

    <div :class="{ 'd-none d-md-block': !expanded }" data-testid="collapsible-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.collapsible-section__toggle {
  font-size: 1.75rem;
  font-weight: bold;
  line-height: 1;
  cursor: pointer;
  padding-left: 0.5rem;
}

@media (max-width: 767.98px) {
  .collapsible-section__header {
    cursor: pointer;
  }
}
</style>
