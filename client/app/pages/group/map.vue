<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useProfileStore } from '~/stores/profile.js'
import { useAuth } from '~/composables/useAuth.js'
import { haversineKm } from '~/composables/useGroupMapGeometry.js'
import { matchesGroupFilters } from '~/utils/groupFilter.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import GroupMap from '~/components/groups/GroupMap.vue'
import GroupInfoModal from '~/components/groups/GroupInfoModal.vue'
import GroupsRequiringModeration from '~/components/networks/NetworkGroupsModerationTable.vue'

// /group/map - PR 887's "Find a group" tab of /group. resources/js/
// components/GroupMapAndList.vue (map+list shell) + GroupMap.vue (the
// Leaflet map itself) are the functional spec; 887 keeps it as a b-tab
// panel URL-rewritten to /group/other, here it's its own Nuxt route with
// /group/nearby and /group/all forwarding in - see GroupsTabsNav.vue's doc
// comment, and stores/groups.js's B7 doc comment for the names/summary
// split this leans on.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()

// /group/all?network=N (linked from the networks page) forwards here with
// its query intact; GroupMap's own network filter narrows the pins, and the
// list follows the viewport so it inherits the filter.
const route = useRoute()
const networkFilter = computed(() => (route.query.network ? Number(route.query.network) : null))

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

// PR 887: a marker click selects a group; the page shows its info modal
// (next event + Go to group). The selected group is always one the map is
// showing, so it's already in `rows` with its summary (image/location/next
// event) hydrated; fall back to the minimal names entry if not.
const selectedGroupId = ref(null)

// What the list's distance column measures from: the searched place if
// there has been a search, else the user's own profile coordinates (GET
// /api/v2/users/me/profile lat/lng), else nothing - the column hides.
const profileStore = useProfileStore()
const searchedPoint = ref(null)
const referencePoint = computed(() => {
  if (searchedPoint.value) return searchedPoint.value

  const p = profileStore.info.data
  if (p && p.lat != null && p.lng != null) return { lat: p.lat, lng: p.lng }

  return null
})
const selectedGroup = computed(() => {
  if (selectedGroupId.value === null) return null
  const row = rows.value.find((r) => r.id === selectedGroupId.value)
  if (row) return row
  const entry = mapGroups.value.find((g) => g.id === selectedGroupId.value)
  return entry ? { id: entry.id, name: entry.name, image: null, location: null, nextEvent: null } : null
})
// What the user has typed or picked in the filter bar (the list's own bar
// reports it). Applied to every group, not just the ones in view, so that
// filtering moves the map rather than only shortening the list under it.
const filters = ref(null)

// Bumped (after the typing stops) to ask the map to frame what the filter now
// matches. Immediately would lurch the map around on every keystroke.
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
  // Don't wake up and touch a component that has gone away.
  if (frameTimer) clearTimeout(frameTimer)
})

// Tag filtering is for the people who can see tags at all - the same people
// who get the moderation queue.
const canManageTags = showModeration

// The profile fetch is best effort, but the map frames itself once, on its
// first sight of the groups - so wait for it to settle before drawing the map,
// or the user's own area would be missed whenever the groups arrived first.
const profileSettled = ref(false)
const loading = computed(() => groupsStore.namesLoading || !profileSettled.value)

// Archived groups are included, as develop's list does: the list badges them
// rather than hiding them, as the old server-rendered page did.
const allGroups = computed(() => groupsStore.names)

// What the map draws: everything the filter allows, wherever it is - so a
// search can take you to a group you can't currently see. (The map applies its
// own network filter.)
const mapGroups = computed(() =>
  allGroups.value.filter((g) => matchesGroupFilters({ name: g.name, tagIds: g.tag_ids }, filters.value))
)

// Where the map is centred, which orders the list by what is nearest when
// there's no better anchor.
const centre = ref(null)

// A user with a town is framed on the groups nearest them; one who has only
// set a country gets a box round the groups in it (develop did this server
// side in GroupController::mine); anyone else gets every group in view.
const yourPoint = computed(() => {
  const p = profileStore.info.data
  return p && p.lat != null && p.lng != null && !isNaN(+p.lat) && !isNaN(+p.lng) ? { lat: +p.lat, lng: +p.lng } : null
})

const yourArea = computed(() => profileStore.info.data?.location || '')

const countryBounds = computed(() => {
  const p = profileStore.info.data
  if (yourPoint.value || !p || !p.country_code) return null

  // The names index carries the country as a name, in the same locale as the
  // profile's country options.
  const name = (p.countries || []).find((c) => c.code === p.country_code)?.name
  if (!name) return null

  const inCountry = allGroups.value.filter(
    (g) => !g.archived_at && g.country === name && g.lat != null && g.lng != null && !isNaN(+g.lat) && !isNaN(+g.lng)
  )
  if (!inCountry.length) return null

  const lats = inCountry.map((g) => +g.lat)
  const lngs = inCountry.map((g) => +g.lng)

  return [
    [Math.min(...lats), Math.min(...lngs)],
    [Math.max(...lats), Math.max(...lngs)],
  ]
})

const effectiveGroupIds = computed(() =>
  groupIdsInBounds.value !== null ? groupIdsInBounds.value : mapGroups.value.map((g) => g.id)
)

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
      // The filter bar's criteria, from the names index (available
      // immediately rather than waiting on per-row hydration).
      tagIds: entry?.tag_ids ?? [],
      // Real km from the reference point (null hides the cell) - the names
      // index carries every group's lat/lng, so no hydration needed. With no
      // anchor the list still opens nearest-the-middle-of-the-map first, but
      // the column stays hidden.
      distance: haversineKm(referencePoint.value ?? centre.value, entry ?? null),
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
  groupsStore.fetchNames({ includeArchived: 'true' })
}

onMounted(() => {
  groupsStore.fetchNames({ includeArchived: 'true' })
  // Best-effort seed for GroupMap's "your groups" pin colour - same
  // memberIds gap as /group/all (stores/groups.js's class doc comment).
  groupsStore.fetchMine().catch(() => {})
  // Best-effort anchor for the distance column; without it (guest profile
  // fetch failure, or no location set) the column just stays hidden.
  profileStore
    .fetchProfileInfo()
    .catch(() => {})
    .finally(() => {
      profileSettled.value = true
    })
})
</script>

<template>
  <div class="container py-4" data-testid="group-map-page">
    <h1 class="d-flex justify-content-between align-items-start">
      <span class="d-flex align-items-center">
        {{ t('groups.groups') }}
        <img src="/images/group_doodle_ico.svg" alt="" class="ms-4" style="height: 76px">
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
        :network="networkFilter"
        :your-group-ids="groupsStore.memberIds"
        :initial-bounds="countryBounds"
        :your-lat="yourPoint ? yourPoint.lat : null"
        :your-lng="yourPoint ? yourPoint.lng : null"
        :your-area="yourArea"
        :frame-request="frameRequest"
        @update:group-ids-in-bounds="groupIdsInBounds = $event"
        @update:centre="centre = $event"
        @select="selectedGroupId = $event"
        @searched="searchedPoint = $event"
      />

      <GroupInfoModal :group="selectedGroup" @close="selectedGroupId = null" />

      <p data-testid="group-map-count">
        <!-- Wording and zero-state from PR 887 (RES-1995 map of groups):
             "... in this area. Search and zoom to find more." with a count, and
             a friendlier "why not find a group near you?" (linking to
             /group/nearby) when nothing is in view. group_count_none carries the
             link inline, as develop's does, so it's rendered with v-html. -->
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-if="rows.length === 0" data-testid="group-map-count-none" v-html="t('groups.group_count_none')" />
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-else v-html="t('groups.group_count_map', { count: rows.length }, rows.length)" />
      </p>

      <!-- Distance column only with an anchor point; nearest-first is the
           default order either way (all-null distances fall back stably). -->
      <GroupsTable
        v-model:hovered-id="hoveredId"
        class="mt-3"
        :groups="rows"
        show-filters
        :show-tags="canManageTags"
        :optional-columns="{ location: true, next_event: true, distance: referencePoint !== null }"
        initial-sort-key="distance"
        @update:filters="onFilters"
      />
    </template>
  </div>
</template>
