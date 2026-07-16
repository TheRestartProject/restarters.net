<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import EventsList from '~/components/events/EventsList.vue'
import EventFilters from '~/components/events/EventFilters.vue'
import { eventIsUpcoming, eventIsInProgress } from '~/composables/useEventComputed.js'

// /party/all - api-contracts-phase-c.md C2: "PartyController::allUpcoming
// doesn't exist on the controller - these routes currently 500 if hit. Not
// a migration gap so much as a pre-existing dead route." Judgment call 5
// proposes this becomes a filter over GET /api/v2/users/me/events: all
// future approved events visible to the user (the union the endpoint
// already returns - mine, nearby and other - deduped by id, since a mine
// event could in principle also carry a stray nearby/all tag).
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('events.events') })

const eventsStore = useEventsStore()
const search = ref('')

const events = computed(() => {
  const seen = new Set()
  const deduped = []
  for (const e of eventsStore.myEvents.data) {
    if (!seen.has(e.id)) {
      seen.add(e.id)
      deduped.push(e)
    }
  }
  return deduped
})

const upcoming = computed(() =>
  events.value
    .filter((e) => e.approved !== false && (eventIsUpcoming(e) || eventIsInProgress(e)))
    .sort((a, b) => new Date(a.start) - new Date(b.start))
)

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return upcoming.value

  return upcoming.value.filter((e) => {
    const haystack = [e.title, e.location, e.group?.name].filter(Boolean).join(' ').toLowerCase()
    return haystack.includes(term)
  })
})

function retry() {
  eventsStore.fetchMyEvents()
}

onMounted(() => {
  eventsStore.fetchMyEvents()
})
</script>

<template>
  <div class="container py-4" data-testid="party-all-page">
    <h1>{{ t('client.events.all_upcoming_title') }}</h1>

    <div v-if="eventsStore.myEvents.loading" data-testid="party-all-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="eventsStore.myEvents.error"
      :model-value="true"
      variant="danger"
      data-testid="party-all-error"
    >
      <p>{{ t('client.events.load_error') }}</p>
      <BButton variant="danger" data-testid="party-all-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <EventFilters v-model:search="search" />

      <EventsList
        :events="filtered"
        :empty-message="search ? t('client.events.no_search_results') : t('groups.no_other_events')"
      />
    </template>
  </div>
</template>
