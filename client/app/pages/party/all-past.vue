<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import EventsList from '~/components/events/EventsList.vue'
import EventFilters from '~/components/events/EventFilters.vue'
import { eventIsFinished } from '~/composables/useEventComputed.js'

// /party/all-past - same dead-route/judgment-call situation as
// pages/party/all.vue (api-contracts-phase-c.md C2, judgment call 5): same
// deduped union, filtered to finished events instead of upcoming ones,
// most-recent-first.
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

const past = computed(() =>
  events.value
    .filter((e) => eventIsFinished(e))
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return past.value

  return past.value.filter((e) => {
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
  <div class="container py-4" data-testid="party-all-past-page">
    <h1>{{ t('client.events.all_past_title') }}</h1>

    <div v-if="eventsStore.myEvents.loading" data-testid="party-all-past-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="eventsStore.myEvents.error"
      :model-value="true"
      variant="danger"
      data-testid="party-all-past-error"
    >
      <p>{{ t('client.events.load_error') }}</p>
      <BButton variant="danger" data-testid="party-all-past-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <EventFilters v-model:search="search" />

      <EventsList
        :events="filtered"
        :empty-message="search ? t('client.events.no_search_results') : t('groups.no_past_events')"
      />
    </template>
  </div>
</template>
