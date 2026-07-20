<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import { useEventsStore } from '~/stores/events.js'

// Gap fix (HIGH): legacy's AddDataModal.vue - a group -> event picker
// launched by FixometerHeading.vue's unconditional "Add Data" button
// (fixometer.vue). Shares its group/event data source with
// components/dashboard/DashboardAddData.vue (GET /api/v2/dashboard's
// your_groups + GET .../users/me/events), but as a modal fetching its own
// data lazily on open, since this button lives outside the authenticated
// dashboard page and must render (and be clickable) for logged-out
// visitors too.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const { loggedIn } = useAuth()
const dashboardStore = useDashboardStore()
const eventsStore = useEventsStore()

const fetching = ref(false)
const fetched = ref(false)

const groups = computed(() => dashboardStore.data?.your_groups ?? [])
const events = computed(() => eventsStore.myEvents.data ?? [])

const groupOptions = computed(() =>
  [...groups.value].sort((a, b) => a.name.localeCompare(b.name)).map((g) => ({ value: g.id, text: g.name }))
)

const selectedGroupId = ref(null)
const groupEvents = computed(() =>
  events.value
    .filter((e) => e.group && e.group.id === selectedGroupId.value)
    .slice()
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)
const eventOptions = computed(() => groupEvents.value.map((e) => ({ value: e.id, text: e.title })))
const selectedEventId = ref(null)

// Keep the selections valid as the data loads/changes - same idiom as
// DashboardAddData.vue.
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

// Fetch once, the first time the modal opens, and only when logged in -
// GET /api/v2/dashboard requires auth, and this button is deliberately
// unconditional (rendered for logged-out visitors too), who should just
// see the "follow a group first" branch below rather than a failed fetch.
watch(
  () => props.show,
  async (visible) => {
    if (!visible || !loggedIn.value || fetched.value) return

    fetching.value = true
    try {
      await Promise.all([dashboardStore.fetch(), eventsStore.fetchMyEvents()])
    } finally {
      fetching.value = false
      fetched.value = true
    }
  },
  { immediate: true }
)

function close() {
  emit('close')
}
</script>

<template>
  <BModal
    :model-value="show"
    :title="t('devices.add_data_button')"
    no-footer
    data-testid="add-data-modal"
    @hide="close"
  >
    <p>{{ t('devices.add_data_description') }}</p>

    <div v-if="fetching" data-testid="add-data-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 2rem" />
      </div>
    </div>

    <template v-else-if="!groupOptions.length">
      <BAlert :model-value="true" variant="warning" data-testid="add-data-no-groups">
        {{ t('devices.add_data_no_groups') }}
      </BAlert>
      <NuxtLink to="/group" class="btn btn-primary" data-testid="add-data-see-groups">
        {{ t('dashboard.see_all_groups') }}
      </NuxtLink>
    </template>

    <template v-else>
      <label class="form-label mt-2" for="add-data-group">{{ `${t('devices.group')}:` }}</label>
      <BFormSelect id="add-data-group" v-model="selectedGroupId" :options="groupOptions" data-testid="add-data-group" />

      <template v-if="!groupEvents.length">
        <BAlert :model-value="true" variant="warning" class="mt-2" data-testid="add-data-no-events">
          {{ t('devices.add_data_no_events') }}
        </BAlert>
        <NuxtLink :to="`/party/create/${selectedGroupId}`" class="btn btn-primary" data-testid="add-data-create-event">
          {{ t('events.create_event') }}
        </NuxtLink>
      </template>

      <template v-else>
        <label class="form-label mt-2" for="add-data-event">{{ `${t('events.event')}:` }}</label>
        <BFormSelect id="add-data-event" v-model="selectedEventId" :options="eventOptions" data-testid="add-data-event" />

        <NuxtLink
          v-if="selectedEventId"
          :to="`/party/view/${selectedEventId}#devices-section`"
          class="btn btn-primary mt-3"
          data-testid="add-data-go"
        >
          {{ t('devices.add_data_button') }}
        </NuxtLink>
      </template>
    </template>
  </BModal>
</template>
