<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModerationStore } from '~/stores/moderation.js'

// Admin / NetworkCoordinator moderation queue, ported from the legacy
// EventsRequiringModeration / GroupsRequiringModeration components (a navigational
// listing of items awaiting approval, each linking to its page where the approve
// controls live). The PARENT page is responsible for role-gating (only render
// this for Administrators / NetworkCoordinators) - the API enforces the same.
const props = defineProps({
  // 'events' | 'groups'
  type: {
    type: String,
    required: true,
    validator: (v) => ['events', 'groups'].includes(v),
  },
  // Optional array of network ids to scope the queue to (mirrors legacy
  // GroupsRequiringModeration/EventsRequiringModeration's `networks` prop -
  // used by /networks/{id} so a NetworkCoordinator only sees THIS network's
  // items, not the platform-wide queue). null (default) = unfiltered, same
  // as the /party and /group/map usages which show every item the API
  // returns for the current user's role.
  //
  // Groups carry their network memberships directly (item.networks, from
  // App\Http\Resources\Group#networks - confirmed via GET /api/v2/moderate/
  // groups). Events don't carry networks themselves - they're scoped via
  // their group's networks (item.group.networks, from App\Http\Resources\
  // Party's nested GroupSummary#networks) - same nesting the legacy
  // EventsRequiringModeration.vue filtered on (`e.group.networks`).
  networks: {
    type: Array,
    required: false,
    default: null,
  },
  // When true, the section (heading + list-or-"None" message) always
  // renders, even on an empty queue - matches legacy NetworkPage.vue, which
  // wraps these components with an always-visible heading and a separate
  // "None" fallback. Defaults to false so /party and /group/map keep their
  // existing behaviour (panel fully hidden when there's nothing to
  // moderate).
  alwaysShow: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const moderationStore = useModerationStore()

const section = computed(() => moderationStore[props.type])

// Client-side network scoping (the /moderate/groups and /moderate/events
// endpoints return every item the user's role can see - there's no
// server-side network filter, same as legacy).
const items = computed(() => {
  const data = section.value.data
  if (!props.networks) return data

  return data.filter((item) => {
    const itemNetworks = props.type === 'events' ? item.group?.networks : item.networks
    return Array.isArray(itemNetworks) && itemNetworks.some((n) => props.networks.includes(n.id))
  })
})

const title = computed(() => t(`client.moderation.${props.type}_title`))

function itemLink(item) {
  return props.type === 'events' ? `/party/view/${item.id}` : `/group/view/${item.id}`
}

onMounted(() => {
  if (props.type === 'events') {
    moderationStore.fetchEvents().catch(() => {})
  } else {
    moderationStore.fetchGroups().catch(() => {})
  }
})
</script>

<template>
  <!-- Only surfaces when there is something to moderate, unless alwaysShow
       forces the heading + "None" message to render regardless (matches
       legacy: the plain /party + /group/map panels hide entirely on an
       empty queue; the /networks/{id} page always shows the heading). -->
  <section
    v-if="alwaysShow || items.length"
    class="panel panel__orange mb-4"
    :data-testid="`moderation-queue-${type}`"
  >
    <h3 class="mb-3">{{ title }}</h3>
    <div v-if="!items.length" class="text-muted" :data-testid="`moderation-queue-${type}-empty`">
      {{ t('client.moderation.none') }}
    </div>
    <ul v-else class="list-unstyled mb-0">
      <li v-for="item in items" :key="item.id" class="mb-2">
        <NuxtLink :to="itemLink(item)" :data-testid="`moderation-queue-${type}-item-${item.id}`">
          {{ item.name || item.title }}
        </NuxtLink>
      </li>
    </ul>
  </section>
</template>
