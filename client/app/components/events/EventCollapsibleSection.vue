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
  // CollapsibleSection's `hide-title` (EventDescription.vue:2): the title row
  // is mobile-only. At md+ the section is always expanded, so a heading and a
  // toggle there would be noise. Without this prop the description section
  // could not use this component at all, and was hand-rolled as a bare
  // `<h2 class="d-md-none">` - which dropped the mobile collapse toggle
  // entirely, so on mobile a long description could not be collapsed.
  hideTitle: {
    type: Boolean,
    default: false,
  },
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
      class="collapsible-title d-flex justify-content-between align-items-center"
      :class="hideTitle ? 'd-md-none' : 'd-md-block'"
      role="button"
      data-testid="event-collapsible-title"
      tabindex="0"
      @click="toggle"
      @keydown.enter.prevent="toggle"
      @keydown.space.prevent="toggle"
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
