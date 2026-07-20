<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModerationStore } from '~/stores/moderation.js'

// Events-awaiting-moderation table for /networks/{id}'s "Events to moderate"
// section (parity-v2/networks.md gap #2 + #11). Legacy NetworkPage.vue
// renders <EventsRequiringModeration :networks="[id]" /> - a full
// GroupEventScrollTable (date / title+group / invited+volunteer counts /
// an edit-approve-duplicate-delete actions dropdown) - not the plain
// title-only <ul> the (now deleted) shared ModerationQueue.vue
// renders on /party and /group/map.
//
// Hand-rolled rather than reproducing GroupEventScrollTable.vue, whose markup
// is coupled to the legacy Vuex store and a dedicated actions-dropdown
// component. The columns match develop's: date, title+group, volunteers
// INVITED, confirmed volunteers, plus a link to the event.
//
// The invited count used to be missing here, on the grounds that it "isn't
// present anywhere in GET /api/v2/moderate/events' payload at all". It wasn't
// - so it was added (Party resource's `invited`, via whenCounted, with the
// endpoint calling loadCount so it costs one query). A missing figure was
// being treated as a fact about the API rather than something to fix.
const props = defineProps({
  // Optional, matching develop's EventsRequiringModeration - which takes no
  // network filter at all on events/index.blade.php:55. null = no filtering.
  networkId: {
    type: Number,
    default: null,
  },
})

const { t, locale } = useI18n()
const moderationStore = useModerationStore()

// Client-side network scoping (events aren't tagged with networks directly -
// they're scoped via their group's networks), same approach as
// ModerationQueue.vue.
const events = computed(() => {
  if (props.networkId == null) return moderationStore.events.data
  return moderationStore.events.data.filter(
    (e) => Array.isArray(e.group?.networks) && e.group.networks.some((n) => n.id === props.networkId)
  )
})

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => {
  moderationStore.fetchEvents().catch(() => {})
})
</script>

<template>
  <div data-testid="network-events-moderation-table">
    <div v-if="!events.length" class="text-muted" data-testid="network-events-moderation-empty">
      {{ t('networks.show.none') }}
    </div>
    <div v-else class="table-responsive table-section">
      <table class="table network-moderation-table">
      <thead>
        <tr>
          <th>{{ t('groups.export.events.date') }}</th>
          <th>{{ t('groups.export.events.event') }}</th>
          <th>{{ t('groups.volunteers_invited') }}</th>
          <th>{{ t('groups.export.events.volunteers') }}</th>
          <th />
        </tr>
      </thead>
      <tbody>
        <tr v-for="event in events" :key="event.id" :data-testid="`network-events-moderation-row-${event.id}`">
          <td>{{ dateLabel(event.start) }}</td>
          <td>
            <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`network-events-moderation-link-${event.id}`">
              {{ event.title }}
            </NuxtLink>
            <div v-if="event.group" class="small">
              <NuxtLink :to="`/group/view/${event.group.id}`">{{ event.group.name }}</NuxtLink>
            </div>
          </td>
          <td>{{ event.invited ?? 0 }}</td>
          <td>{{ event.stats?.volunteers ?? 0 }}</td>
          <td>
            <NuxtLink
              :to="`/party/edit/${event.id}`"
              class="network-moderation-flag"
              :data-testid="`network-events-moderation-flag-${event.id}`"
            >
              {{ t('partials.event_requires_moderation') }}
            </NuxtLink>
          </td>
        </tr>
      </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped lang="scss">
.network-moderation-flag {
  white-space: nowrap;
}
</style>
