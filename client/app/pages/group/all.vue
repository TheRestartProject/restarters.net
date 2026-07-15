<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useColumnPreferences } from '~/composables/useColumnPreferences.js'
import GroupsTabsNav from '~/components/groups/GroupsTabsNav.vue'
import GroupsTable from '~/components/groups/GroupsTable.vue'

// /group/all - resources/views/group/index.blade.php (tab="all") +
// resources/js/components/GroupsPage.vue's "All Groups" tab /
// GroupsTable.vue + GroupsTableFilters.vue are the functional spec.
//
// GET /api/v2/groups/names only returns {id, name, archived_at} today (the
// location/hosts/restarters/tags/next_event summary fields land with
// PR #887 - api-contracts-phase-b.md client notes, docs/nuxt-migration/
// api-gaps.md). So: search/sort/paginate client-side over the names index,
// then hydrate full details (GET /api/v2/groups/{id}) only for the rows on
// the current page - not all of them. Network/country/tag filters from the
// legacy GroupsTableFilters are dropped for now (that data isn't in the
// names index at all yet) - TODO(PR #887): restore them once the summary
// endpoint carries location/networks/tags.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('groups.groups') })

const groupsStore = useGroupsStore()
const { columns: optionalColumns, toggle: toggleColumn } = useColumnPreferences('all-groups', {
  location: true,
  hosts: true,
  restarters: true,
  next_event: true,
})

const PAGE_SIZE = 20

const search = ref('')
const includeArchived = ref(false)
const page = ref(1)

const filteredNames = computed(() => {
  const term = search.value.trim().toLowerCase()

  return groupsStore.names
    .filter((g) => (includeArchived.value ? true : !g.archived_at))
    .filter((g) => !term || g.name.toLowerCase().includes(term))
    .sort((a, b) => a.name.localeCompare(b.name))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredNames.value.length / PAGE_SIZE)))

const pageNames = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE
  return filteredNames.value.slice(start, start + PAGE_SIZE)
})

const rows = computed(() =>
  pageNames.value.map((entry) => {
    const details = groupsStore.details[entry.id]

    return {
      id: entry.id,
      name: entry.name,
      archivedAt: entry.archived_at,
      location: details?.location ?? null,
      hosts: details?.hosts ?? null,
      restarters: details?.restarters ?? null,
      nextEvent: details?.next_event ?? null,
      isMember: groupsStore.isMember(entry.id),
    }
  })
)

// Hydrate full details for whichever rows are currently visible.
watch(
  pageNames,
  (entries) => {
    entries.forEach((entry) => groupsStore.fetchDetails(entry.id))
  },
  { immediate: true }
)

// Search/archived-toggle changes invalidate the current page.
watch([search, includeArchived], () => {
  page.value = 1
})

function previousPage() {
  if (page.value > 1) page.value -= 1
}

function nextPage() {
  if (page.value < totalPages.value) page.value += 1
}

function retry() {
  groupsStore.fetchNames({ includeArchived: 'true' })
}

onMounted(() => {
  // Fetch the full set (including archived) once; the archived toggle then
  // just filters client-side rather than re-fetching.
  groupsStore.fetchNames({ includeArchived: 'true' })
})
</script>

<template>
  <div class="container py-4" data-testid="group-all-page">
    <h1 class="d-flex justify-content-between align-items-start">
      {{ t('groups.groups') }}
      <NuxtLink to="/group/create" class="btn btn-primary" data-testid="group-create-link">
        {{ t('groups.create_groups') }}
      </NuxtLink>
    </h1>

    <GroupsTabsNav active="all" />

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
      <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
        <input
          v-model="search"
          type="search"
          class="form-control"
          style="max-width: 20rem"
          :placeholder="t('groups.search_name_placeholder')"
          data-testid="group-all-search"
        >
        <div class="form-check">
          <input
            id="group-all-include-archived"
            v-model="includeArchived"
            type="checkbox"
            class="form-check-input"
            data-testid="group-all-include-archived"
          >
          <label class="form-check-label" for="group-all-include-archived">
            {{ t('client.groups.include_archived') }}
          </label>
        </div>
      </div>

      <fieldset class="mb-3">
        <legend class="col-form-label small">
          {{ t('client.groups.columns_label') }}
        </legend>
        <div class="d-flex flex-wrap gap-3">
          <div v-for="col in ['location', 'hosts', 'restarters', 'next_event']" :key="col" class="form-check">
            <input
              :id="`group-all-column-${col}`"
              :checked="optionalColumns[col]"
              type="checkbox"
              class="form-check-input"
              :data-testid="`group-all-column-toggle-${col}`"
              @change="toggleColumn(col)"
            >
            <label class="form-check-label" :for="`group-all-column-${col}`">
              {{ t(`client.groups.column_${col}`) }}
            </label>
          </div>
        </div>
      </fieldset>

      <p data-testid="group-all-count">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('groups.group_count', { count: filteredNames.length }, filteredNames.length)" />
      </p>

      <GroupsTable :groups="rows" :optional-columns="optionalColumns" />

      <div class="d-flex justify-content-between align-items-center mt-3">
        <BButton
          variant="secondary"
          :disabled="page <= 1"
          data-testid="group-all-prev-page"
          @click="previousPage"
        >
          {{ t('client.groups.previous_page') }}
        </BButton>
        <span data-testid="group-all-page-indicator">
          {{ t('client.groups.page_of', { page, total: totalPages }) }}
        </span>
        <BButton
          variant="secondary"
          :disabled="page >= totalPages"
          data-testid="group-all-next-page"
          @click="nextPage"
        >
          {{ t('client.groups.next_page') }}
        </BButton>
      </div>
    </template>
  </div>
</template>
