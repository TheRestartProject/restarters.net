<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { matchesGroupFilters } from '~/utils/groupFilter.js'
import GroupMap from '~/components/groups/GroupMap.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import GroupInfoModal from '~/components/groups/GroupInfoModal.vue'

// The Groups section of /networks/{id}: a map and a filterable list of the
// network's groups, as develop's NetworkPage.vue embeds GroupMapAndList
// (initial-bounds = the inverted whole-world box, :network, show-filters,
// tag filter for people who can manage tags). Same composition as
// pages/group/map.vue, narrowed to one network: the map starts zoomed out so
// every group in the network is in view, and the list follows the viewport.
const props = defineProps({
  networkId: {
    type: Number,
    required: true,
  },
  // Administrators / this network's coordinators see the Tag filter.
  canManageTags: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const groupsStore = useGroupsStore()

// null = the map hasn't reported what's in view yet; [] is a real answer
// (see pages/group/map.vue).
const groupIdsInBounds = ref(null)
const hoveredId = ref(null)
const selectedGroupId = ref(null)

// The filter bar's criteria, applied to every group in the network (not just
// those in view) so that filtering moves the pins rather than only shortening
// the list under them. Bumping frameRequest, after the typing stops, asks the
// map to frame what is now matched; immediately would lurch the map around on
// every keystroke.
const filters = ref(null)
const REFRAME_DEBOUNCE_MS = 500
const frameRequest = ref(0)
let frameTimer = null

function onFilters(value) {
  filters.value = value

  if (frameTimer) clearTimeout(frameTimer)
  frameTimer = setTimeout(() => {
    frameTimer = null
    frameRequest.value++
  }, REFRAME_DEBOUNCE_MS)
}

onBeforeUnmount(() => {
  if (frameTimer) clearTimeout(frameTimer)
})

// The names index carries every group's network_ids, so the network filter
// needs no per-group hydration. Archived groups are not on the map.
const mapGroups = computed(() =>
  groupsStore.names.filter(
    (g) =>
      !g.archived_at &&
      (g.network_ids || []).includes(props.networkId) &&
      matchesGroupFilters({ name: g.name, tagIds: g.tag_ids }, filters.value)
  )
)

const effectiveGroupIds = computed(() =>
  groupIdsInBounds.value !== null ? groupIdsInBounds.value : mapGroups.value.map((g) => g.id)
)

const rows = computed(() =>
  effectiveGroupIds.value.map((id) => {
    const entry = mapGroups.value.find((g) => g.id === id)
    const summary = groupsStore.summaryByIds[id]

    return {
      id,
      name: summary?.name ?? entry?.name ?? '',
      archivedAt: summary?.archived_at ?? entry?.archived_at ?? null,
      image: summary?.image ?? null,
      location: summary?.location ?? null,
      hosts: summary?.hosts ?? null,
      restarters: summary?.restarters ?? null,
      nextEvent: summary?.next_event ?? null,
      networkIds: entry?.network_ids ?? [],
      tagIds: entry?.tag_ids ?? [],
      isMember: groupsStore.isMember(id),
    }
  })
)

const selectedGroup = computed(() => {
  if (selectedGroupId.value === null) return null
  const row = rows.value.find((r) => r.id === selectedGroupId.value)
  if (row) return row
  const entry = mapGroups.value.find((g) => g.id === selectedGroupId.value)
  return entry ? { id: entry.id, name: entry.name, image: null, location: null, nextEvent: null } : null
})

watch(
  effectiveGroupIds,
  (ids) => {
    if (ids.length) groupsStore.fetchSummaries(ids)
  },
  { immediate: true }
)

onMounted(() => {
  groupsStore.fetchNames().catch(() => {})
  groupsStore.fetchMine().catch(() => {})
})
</script>

<template>
  <div data-testid="network-groups-map-list">
    <div v-if="groupsStore.namesLoading" data-testid="network-groups-map-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 400px" />
      </div>
    </div>

    <BAlert v-else-if="groupsStore.namesError" :model-value="true" variant="danger" data-testid="network-groups-map-error">
      {{ t('client.groups.load_error') }}
    </BAlert>

    <template v-else>
      <GroupMap
        v-model:hovered-id="hoveredId"
        :groups="mapGroups"
        :network="networkId"
        :your-group-ids="groupsStore.memberIds"
        :frame-request="frameRequest"
        @update:group-ids-in-bounds="groupIdsInBounds = $event"
        @select="selectedGroupId = $event"
      />

      <GroupInfoModal :group="selectedGroup" @close="selectedGroupId = null" />

      <GroupsTable
        v-model:hovered-id="hoveredId"
        class="mt-3"
        :groups="rows"
        show-filters
        :show-tags="canManageTags"
        :optional-columns="{ location: true, next_event: true }"
        @update:filters="onFilters"
      />
    </template>
  </div>
</template>
