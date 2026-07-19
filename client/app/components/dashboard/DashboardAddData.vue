<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

// Legacy DashboardAddData.vue: a group -> event picker that jumps to the
// chosen event's view (/party/view/{id}) where the user adds repair data.
// Rendered only when the user has at least one event to add data to. Data is
// passed in by the page (dashboard your_groups + GET /api/v2/users/me/events),
// matching the sibling dashboard components' prop-driven shape.
const props = defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
  events: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()

const selectedGroupId = ref(null)
const selectedEventId = ref(null)

// Only groups that actually have an event to add data to.
const groupOptions = computed(() =>
  props.groups
    .filter((g) => props.events.some((e) => e.group && e.group.id === g.id))
    .slice()
    .sort((a, b) => a.name.localeCompare(b.name))
    .map((g) => ({ value: g.id, text: g.name }))
)

const groupEvents = computed(() =>
  props.events
    .filter((e) => e.group && e.group.id === selectedGroupId.value)
    .slice()
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)
const eventOptions = computed(() => groupEvents.value.map((e) => ({ value: e.id, text: e.title })))

// Keep the group/event selections valid as the data loads/changes.
watch(
  groupOptions,
  (opts) => {
    if (!opts.some((o) => o.value === selectedGroupId.value)) {
      selectedGroupId.value = opts.length ? opts[0].value : null
    }
  },
  { immediate: true }
)
watch(
  groupEvents,
  (list) => {
    if (!list.some((e) => e.id === selectedEventId.value)) {
      selectedEventId.value = list.length ? list[0].id : null
    }
  },
  { immediate: true }
)

const addUrl = computed(() => (selectedEventId.value ? `/party/view/${selectedEventId.value}` : null))
</script>

<template>
  <div v-if="groupOptions.length" class="panel" data-testid="dashboard-add-data">
    <div class="d-flex align-items-center">
      <h2 class="mb-0">{{ t('dashboard.add_data_heading') }}</h2>
      <img src="/images/fixometer-doodle.svg" alt="" class="ms-4 d-none d-md-block add-data__doodle">
    </div>
    <div class="content-divider" />
    <p>{{ t('dashboard.see_your_impact') }}:</p>
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <BFormSelect v-model="selectedGroupId" :options="groupOptions" data-testid="add-data-group" />
      </div>
      <div class="col-md-5">
        <BFormSelect v-model="selectedEventId" :options="eventOptions" data-testid="add-data-event" />
      </div>
      <div class="col-md-2 d-flex justify-content-md-end">
        <NuxtLink v-if="addUrl" :to="addUrl" class="btn btn-primary" data-testid="add-data-add">
          {{ t('dashboard.add_data_add') }}
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.add-data__doodle {
  height: 2.5rem;
}
</style>
