<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useAuth } from '~/composables/useAuth.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import GroupMap from '~/components/groups/GroupMap.vue'
import GroupsRequiringModeration from '~/components/networks/NetworkGroupsModerationTable.vue'

// /group/map - resources/js/components/GroupMapAndList.vue (map+list shell)
// + GroupMap.vue (the Leaflet map itself) are the functional spec. Unlike
// legacy, where the map lived as a tab panel of /group/{nearby,other}
// (GroupsPage.vue), this is its own route: /group/nearby was already
// ported as a plain list page (B4), so the map gets its own tab/URL rather
// than folding back into that page - see GroupsTabsNav.vue's doc comment
// and stores/groups.js's B7 doc comment for the names/summary split this
// leans on.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()

// Groups-requiring-moderation queue for Administrators / NetworkCoordinators
// (legacy showed a "Groups requiring moderation" panel above the group list/map
// for those roles).
const { hasRole } = useAuth()
const showModeration = computed(() => hasRole('Administrator') || hasRole('NetworkCoordinator'))

// null = the map hasn't told us what's in view yet; an empty array is a
// real answer (nothing in view) and must not fall back to every group -
// see resources/js/components/GroupMapAndList.vue:96-98's own doc comment
// for this exact distinction (a past regression: an empty bounds report was
// treated the same as "no report yet", so panning to an empty area kept
// listing every group).
const groupIdsInBounds = ref(null)
const hoveredId = ref(null)
const search = ref('')

const loading = computed(() => groupsStore.namesLoading)

// The map always shows every non-archived group (subject to its own
// network filter, unused on this generic page) - only the list below is
// further narrowed by the current viewport and the search box.
const mapGroups = computed(() => groupsStore.names.filter((g) => !g.archived_at))

const effectiveGroupIds = computed(() => {
  const base = groupIdsInBounds.value !== null ? groupIdsInBounds.value : mapGroups.value.map((g) => g.id)

  const term = search.value.trim().toLowerCase()
  if (!term) return base

  const byId = new Map(mapGroups.value.map((g) => [g.id, g]))
  return base.filter((id) => (byId.get(id)?.name || '').toLowerCase().includes(term))
})

// Rows for the list panel: names-index fields first, then whatever
// fetchSummaries has hydrated for this id (location/hosts/restarters/next
// event) - same layering as all.vue's `rows` computed.
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
      isMember: groupsStore.isMember(id),
    }
  })
)

// Hydrate summaries for whichever rows are currently visible (bounds +
// search) - fetchSummaries itself skips ids already hydrated/in flight.
watch(
  effectiveGroupIds,
  (ids) => {
    if (ids.length) groupsStore.fetchSummaries(ids)
  },
  { immediate: true }
)

function retry() {
  groupsStore.fetchNames()
}

onMounted(() => {
  groupsStore.fetchNames()
  // Best-effort seed for GroupMap's "your groups" pin colour - same
  // memberIds gap as /group/all (stores/groups.js's class doc comment).
  groupsStore.fetchMine().catch(() => {})
})
</script>

<template>
  <div class="container py-4" data-testid="group-map-page">
    <h1 class="d-flex justify-content-between align-items-start">
      <span class="d-flex align-items-center">
        {{ t('groups.groups') }}
        <img src="/images/group_doodle_ico.svg" alt="" class="ms-3" style="height: 76px">
      </span>
      <NuxtLink to="/group/create" class="btn btn-primary" data-testid="group-create-link">
        <span class="d-block d-lg-none">{{ t('groups.create_groups_mobile2') }}</span>
        <span class="d-none d-lg-block">{{ t('groups.create_groups') }}</span>
      </NuxtLink>
    </h1>

    <!-- group/index.blade.php:53 - the full GroupsTable(approve), not
         the plain name-only list. These three pages are tabs of develop\'s
         single /group page, which renders it once above the tabs. -->
    <GroupsRequiringModeration v-if="showModeration"  />

    <GroupsTabsNav active="map" />

    <div v-if="loading" data-testid="group-map-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 400px" />
      </div>
    </div>

    <BAlert v-else-if="groupsStore.namesError" :model-value="true" variant="danger" data-testid="group-map-error">
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-map-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <GroupMap
        v-model:hovered-id="hoveredId"
        :groups="mapGroups"
        :your-group-ids="groupsStore.memberIds"
        @update:group-ids-in-bounds="groupIdsInBounds = $event"
      />

      <div class="d-flex flex-wrap gap-3 align-items-center my-3">
        <input
          v-model="search"
          type="search"
          class="form-control"
          style="max-width: 20rem"
          :placeholder="t('groups.search_name_placeholder')"
          data-testid="group-map-search"
        >
      </div>

      <p data-testid="group-map-count">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('groups.group_count_map', { count: rows.length }, rows.length)" />
      </p>

      <GroupsTable v-model:hovered-id="hoveredId" :groups="rows" />
    </template>
  </div>
</template>
