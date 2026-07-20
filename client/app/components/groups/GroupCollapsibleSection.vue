<script setup>
import { ref } from 'vue'

// Simplified port of develop's CollapsibleSection.vue: always fully expanded
// on desktop; on mobile, starts collapsed (title + count badge + expand/
// collapse icon) unless `collapsed` is false. Used to wrap the About and
// Volunteers sections (parity gap 12) - the "one component per legacy title/
// content/title-right slot" API is trimmed down to the two slots this task
// actually needs.
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
      @click="toggle"
    >
      <div class="d-flex align-items-center">
        <slot name="title" />
        <BBadge v-if="count" variant="primary" pill class="ms-2 d-md-none">{{ count }}</BBadge>
      </div>
      <button type="button" class="collapsible-toggle d-md-none" :aria-expanded="expanded">
        <img v-if="expanded" :src="'/images/minus-icon.svg'" alt="" width="20" height="20">
        <img v-else :src="'/images/add-icon.svg'" alt="" width="20" height="20">
      </button>
    </div>
    <div :class="{ 'd-none': !expanded, 'd-md-block': true }">
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
