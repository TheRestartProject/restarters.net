<script setup>
import { computed } from 'vue'

// A device-search table column header. When `sortKey` is given the header is a
// clickable sort control (up/down arrows, active direction highlighted) that
// emits `sort` with its key - matching the legacy FixometerRecordsTable's
// sortable b-table columns (Item/Category/Brand/Group/Status/Date). Columns
// with no sortKey (Assessment, the info column) render as plain text, as they
// did in legacy.
const props = defineProps({
  label: { type: String, required: true },
  sortKey: { type: String, default: null },
  activeKey: { type: String, default: null },
  desc: { type: Boolean, default: false },
})

const emit = defineEmits(['sort'])

const isActive = computed(() => props.sortKey !== null && props.activeKey === props.sortKey)
</script>

<template>
  <button
    v-if="sortKey"
    type="button"
    class="sort-header"
    :aria-sort="isActive ? (desc ? 'descending' : 'ascending') : 'none'"
    :data-testid="`device-search-sort-${sortKey}`"
    @click="emit('sort', sortKey)"
  >
    <span>{{ label }}</span>
    <span class="sort-header__arrows" aria-hidden="true">
      <span class="sort-header__up" :class="{ 'sort-header__on': isActive && !desc }">▲</span>
      <span class="sort-header__down" :class="{ 'sort-header__on': isActive && desc }">▼</span>
    </span>
  </button>
  <span v-else>{{ label }}</span>
</template>

<style scoped lang="scss">
.sort-header {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0;
  border: 0;
  background: none;
  font-weight: bold;
  color: inherit;
  cursor: pointer;
}

.sort-header__arrows {
  display: inline-flex;
  flex-direction: column;
  line-height: 0.6;
  font-size: 0.55rem;
}

// Both arrows dimmed by default; the active direction is picked out in the
// brand teal - the legacy b-table sort-icon treatment.
.sort-header__up,
.sort-header__down {
  color: #b7c0cc;
}

.sort-header__on {
  color: #0394a6;
}
</style>
