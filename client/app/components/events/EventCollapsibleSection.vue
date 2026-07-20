<script setup>
import { ref } from 'vue'

// Port of develop's CollapsibleSection.vue for the events pages (gaps 7, 12,
// 23): always fully expanded on desktop; on mobile, starts collapsed
// (title + optional count badge + expand/collapse icon) unless `collapsed`
// is false. Mirrors components/groups/GroupCollapsibleSection.vue's trimmed
// two-slot API (that component is group-owned, so this is a small
// events-owned copy rather than a cross-boundary import) - kept in sync
// deliberately, not accidentally duplicated.
const props = defineProps({
  collapsed: {
    type: Boolean,
    default: true,
  },
  count: {
    type: Number,
    default: null,
  },
})

const expanded = ref(!props.collapsed)

function toggle() {
  expanded.value = !expanded.value
}
</script>

<template>
  <div>
    <div
      class="collapsible-title d-flex d-md-block justify-content-between align-items-center"
      role="button"
      data-testid="event-collapsible-title"
      @click="toggle"
    >
      <div class="d-flex align-items-center">
        <slot name="title" />
        <BBadge v-if="count" variant="primary" pill class="ms-2 d-md-none" data-testid="event-collapsible-count-badge">{{ count }}</BBadge>
      </div>
      <button type="button" class="collapsible-toggle d-md-none" :aria-expanded="expanded">
        <img v-if="expanded" src="/images/minus-icon.svg" alt="" width="20" height="20">
        <img v-else src="/images/add-icon.svg" alt="" width="20" height="20">
      </button>
    </div>
    <div :class="{ 'd-none': !expanded, 'd-md-block': true }" data-testid="event-collapsible-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.collapsible-title {
  cursor: default;
}

@media (max-width: 767.98px) {
  .collapsible-title {
    cursor: pointer;
  }
}

.collapsible-toggle {
  border: 0;
  background: none;
  padding: 0;
}
</style>
