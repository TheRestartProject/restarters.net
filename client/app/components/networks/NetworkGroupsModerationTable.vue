<script setup>
import { computed, onMounted } from 'vue'
import { useModerationStore } from '~/stores/moderation.js'
import GroupsTable from '~/components/groups/GroupsTable.vue'

// Groups-awaiting-moderation table for /networks/{id}'s "Groups to moderate"
// section (parity-v2/networks.md gap #2 + #11). Legacy NetworkPage.vue
// renders <GroupsRequiringModeration :networks="[id]" /> - the FULL
// GroupsTable(approve) (logo / name+archived-badge+tags / location / hosts /
// restarters / next-event, plus a trailing "Group requires moderation" link
// column) - not the plain name-only <ul> the shared components/moderation/
// ModerationQueue.vue renders on /party and /group/map.
//
// Renders GroupsTable in `approve` mode, exactly as develop's
// GroupsRequiringModeration does - it is nothing but a fetching wrapper
// around <GroupsTable :groups approve />. This used to hand-roll its own
// plain-text-header table, which cost the moderation queue the panel
// treatment, the icon column headers, the sorting and the amber
// requires-moderation cell all at once.
const props = defineProps({
  // Optional, matching develop's GroupsRequiringModeration `networks` prop
  // (required:false, default null). null = no network filtering, which is what
  // /group needs - develop renders this same component there with the viewer's
  // own network ids, not a single network's.
  networkId: {
    type: Number,
    default: null,
  },
})

const moderationStore = useModerationStore()

// Client-side network scoping, same approach as ModerationQueue.vue - the
// /moderate/groups endpoint returns every group the caller's role can see,
// there's no server-side network filter (matching legacy).
const groups = computed(() => {
  if (props.networkId == null) return moderationStore.groups.data
  return moderationStore.groups.data.filter(
    (g) => Array.isArray(g.networks) && g.networks.some((n) => n.id === props.networkId)
  )
})

onMounted(() => {
  moderationStore.fetchGroups().catch(() => {})
})
</script>

<template>
  <div data-testid="network-groups-moderation-table">
    <!-- The "None" placeholder belongs to the caller, not here.
         GroupsRequiringModeration.vue is `v-if="loaded && groups.length"` and
         renders nothing when empty; NetworkPage.vue supplies its own
         `networks.show.none` line under the section's h2. Owning it here put a
         bare, context-free "None" at the top of /group/all, which reuses this
         component without a heading. -->
    <slot v-if="!groups.length" name="empty" />
    <!-- GroupsRequiringModeration.vue:3 wraps the table in
         `<section class="table-section">` - _tables.scss's white/20px-padding/
         1px-black-border/6px-shadow panel, which we had already ported but
         never applied here. That is where the missing panel came from. -->
    <section v-else class="table-section">
      <GroupsTable :groups="groups" :show-join="false" :show-filters="false" approve />
    </section>
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
