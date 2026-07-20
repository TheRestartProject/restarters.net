<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useAuth } from '~/composables/useAuth.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'
import ModerationQueue from '~/components/moderation/ModerationQueue.vue'

// /group/all - resources/views/group/index.blade.php (tab="all") +
// resources/js/components/GroupsPage.vue's "All Groups" tab /
// GroupsTable.vue + GroupsTableFilters.vue are the functional spec.
//
// GET /api/v2/groups/names carries {id, name, lat, lng, country,
// network_ids, tag_ids, archived_at} (PR #887 - api-contracts-phase-b.md
// client notes, docs/nuxt-migration/api-gaps.md); `network_ids`/`tag_ids`
// power GroupsTable's network/tag filter dropdowns immediately, without
// waiting on per-row hydration (see `rows` below). Full location/hosts/
// restarters/next_event/tags (for the tag *badges*, as opposed to the
// filter) still only arrive via GET /api/v2/groups/{id} - see fetchDetails
// below. Name/tag/location/country/network search/sort is GroupsTable's
// own built-in filter bar (show-filters, gap #6) rather than a page-level
// search box, so it applies over the full list, not just whichever rows
// happen to be hydrated. There is no pagination (gap #7, matching legacy):
// every filtered row renders, and full details are hydrated for all of
// them (not just a "current page") - each request is cached in
// groupsStore.details, so filtering/re-rendering afterwards is free.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()

// Groups-requiring-moderation queue, and per-row tag badges, for
// Administrators / NetworkCoordinators (legacy: GroupsRequiringModeration
// above the shared tabs on every /group tab, and showTags on All Groups -
// parity-v2/groups-lists.md gaps #2 and #11).
const { hasRole } = useAuth()
const showModeration = computed(() => hasRole('Administrator') || hasRole('NetworkCoordinator'))
const showTags = computed(() => hasRole('Administrator') || hasRole('NetworkCoordinator'))

// Legacy has no "hide archived groups" toggle anywhere - archived groups
// are always listed, just badge-marked (GroupArchivedBadge) - so there is
// no client-side narrowing here beyond the sort (gap #14/#3 in the
// original diff pass; the API call below still fetches the full set,
// archived included).
const sortedNames = computed(() => [...groupsStore.names].sort((a, b) => a.name.localeCompare(b.name)))

const rows = computed(() =>
  sortedNames.value.map((entry) => {
    const details = groupsStore.details[entry.id]

    return {
      id: entry.id,
      name: entry.name,
      archivedAt: entry.archived_at,
      image: details?.image ?? null,
      tags: details?.tags ?? null,
      tagIds: entry.tag_ids ?? [],
      networkIds: entry.network_ids ?? [],
      location: details?.location ?? null,
      hosts: details?.hosts ?? null,
      restarters: details?.restarters ?? null,
      nextEvent: details?.next_event ?? null,
      isMember: groupsStore.isMember(entry.id),
    }
  })
)

// Hydrate full details for every row in the (sorted) list - see the class
// doc comment above for why this isn't limited to a "current page".
watch(
  sortedNames,
  (entries) => {
    entries.forEach((entry) => groupsStore.fetchDetails(entry.id))
  },
  { immediate: true }
)

function retry() {
  groupsStore.fetchNames({ includeArchived: 'true' })
}

onMounted(() => {
  // Fetch the full set, including archived groups - legacy always shows
  // them (badge-marked), so there's no separate "include archived" toggle
  // to gate this on.
  groupsStore.fetchNames({ includeArchived: 'true' })
})
</script>

<template>
  <div class="container py-4" data-testid="group-all-page">
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

    <ModerationQueue v-if="showModeration" type="groups" />

    <GroupsTabsNav active="all">
      <div v-if="groupsStore.namesLoading" data-testid="group-all-loading">
        <div class="placeholder-glow mb-3">
          <span class="placeholder col-12" style="height: 6rem" />
        </div>
      </div>

      <BAlert v-else-if="groupsStore.namesError" :model-value="true" variant="danger" data-testid="group-all-error">
        <p>{{ t('client.groups.load_error') }}</p>
        <BButton variant="danger" data-testid="group-all-retry" @click="retry">
          {{ t('client.dashboard.retry') }}
        </BButton>
      </BAlert>

      <template v-else>
        <p data-testid="group-all-count">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="t('groups.group_count', { count: rows.length }, rows.length)" />
        </p>

        <GroupsTable :groups="rows" :show-filters="true" :show-tags="showTags" />
      </template>
    </GroupsTabsNav>
  </div>
</template>
