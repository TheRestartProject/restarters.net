<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModerationStore } from '~/stores/moderation.js'
import GroupsTable from '~/components/groups/GroupsTable.vue'

// Groups-awaiting-moderation table for /networks/{id}'s "Groups to moderate"
// section (parity-v2/networks.md gap #2 + #11). Legacy NetworkPage.vue
// renders <GroupsRequiringModeration :networks="[id]" /> - the FULL
// GroupsTable(approve) (logo / name+archived-badge+tags / location / hosts /
// restarters / next-event, plus a trailing "Group requires moderation" link
// column) - not the plain name-only <ul> the shared components/moderation/
// ModerationQueue.vue renders on /party and /group/map. Built as a
// Renders GroupsTable in `approve` mode, exactly as develop's
// GroupsRequiringModeration does - it is nothing but a fetching wrapper
// around <GroupsTable :groups approve />. This used to hand-roll its own
// plain-text-header table, which cost the moderation queue the panel
// treatment, the icon column headers, the sorting and the amber
// requires-moderation cell all at once.
// vue (which has no "approve" trailing-column mode and is out of scope for
// this pass - see parity-v2/networks.md gap #2's write-up) or extending
// ModerationQueue.vue (shared with /party and /group/map, also out of
// scope). GET /api/v2/moderate/groups already returns the full Group
// resource (image/location/hosts/restarters/next_event/tags/archived_at/
// networks), so every legacy column is available here.
const props = defineProps({
  networkId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const moderationStore = useModerationStore()

// Client-side network scoping, same approach as ModerationQueue.vue - the
// /moderate/groups endpoint returns every group the caller's role can see,
// there's no server-side network filter (matching legacy).
const groups = computed(() =>
  moderationStore.groups.data.filter(
    (g) => Array.isArray(g.networks) && g.networks.some((n) => n.id === props.networkId)
  )
)

onMounted(() => {
  moderationStore.fetchGroups().catch(() => {})
})
</script>

<template>
  <div data-testid="network-groups-moderation-table">
    <div v-if="!groups.length" class="text-muted" data-testid="network-groups-moderation-empty">
      {{ t('networks.show.none') }}
    </div>
    <GroupsTable v-else :groups="groups" :show-join="false" :show-filters="false" approve />
  </div>
</template>

<style scoped lang="scss">
.network-moderation-photo-col {
  width: 90px;
}

.network-moderation-photo {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border: 1px solid #000;
}

.tag-badge {
  font-size: 0.75rem;
  font-weight: normal;
  padding: 0.2em 0.5em;
}

.network-moderation-flag {
  white-space: nowrap;
}
</style>
