<script setup>
import { computed, ref } from 'vue'
import { sortAriaValue, sortHintKey } from '../../composables/useSortAria.js'
import { useI18n } from 'vue-i18n'
import { useGroupRole } from '../../composables/useGroupRole.js'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import GroupJoinButton from './GroupJoinButton.vue'
import GroupsTableFilters from './GroupsTableFilters.vue'

// Sortable groups table shared by /group (mine), /group/nearby, /group/all
// and /group/map (resources/js/components/GroupsTable.vue is the functional
// spec - column set, icon-only headers and sort behaviour, not just markup).
//
// Rows are pre-normalised by the page: {id, name, archivedAt, role, image,
// tags, location: {location, country} | null, hosts, restarters,
// nextEvent: {start} | null, isMember}. Any optional field may be
// null/undefined (e.g. All Groups rows only get location/hosts/restarters/
// nextEvent once GroupsStore#fetchDetails has hydrated them - see
// stores/groups.js and docs/nuxt-migration/api-gaps.md).
const props = defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
  optionalColumns: {
    type: Object,
    default: () => ({ location: true, hosts: true, restarters: true, next_event: true }),
  },
  showRole: {
    type: Boolean,
    default: false,
  },
  // Tag badges under the group name (legacy: Administrator/NetworkCoordinator
  // only, gated by the page - GET /api/v2/groups/{id}'s `tags` is already
  // filtered server-side to what the caller's role may see).
  showTags: {
    type: Boolean,
    default: false,
  },
  showJoin: {
    type: Boolean,
    default: true,
  },
  // GroupsTable.vue's `approve` (develop): swaps the join column for an amber
  // "requires moderation" cell linking to the group's edit page. develop's
  // GroupsRequiringModeration is nothing but a fetching wrapper around
  // <GroupsTable :groups approve />, so the moderation queue gets this whole
  // table for free - panel, icon column headers, sorting. Ours previously
  // hand-rolled a separate plain-text-header table for it and lost all three.
  approve: {
    type: Boolean,
    default: false,
  },
  // Optional row<->marker hover linking for /group/map (GroupMap.vue): the
  // id whose row/pin should be highlighted, and update:hoveredId to report
  // back which row the pointer is over. Unused (stays null, no visual
  // effect) by /group and /group/all - resources/js/components/
  // GroupMapAndList.vue's hover.sync between GroupMap and GroupsTable is
  // the functional spec (Neil's PR feedback there: hovering a pin
  // highlights the matching row, and vice versa).
  hoveredId: {
    type: Number,
    default: null,
  },
  // Show the name/location/country/tags filter bar (GroupsTableFilters.vue).
  showFilters: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:hoveredId'])

const { t, locale } = useI18n()
const { roleLabelKey, roleVariant } = useGroupRole()
const { uploadedImageUrl } = useUploadedImageUrl()

const DEFAULT_PROFILE = '/images/placeholder-avatar.webp'

function imageSrc(row) {
  return uploadedImageUrl(row.image) || DEFAULT_PROFILE
}

const sortKey = ref('name')
const sortDesc = ref(false)

const filters = ref({ name: '', location: '', country: '', tags: [], network: '' })

function matchesFilters(row) {
  const f = filters.value
  const has = (haystack, needle) =>
    !needle || String(haystack || '').toLowerCase().includes(needle.toLowerCase())

  // Network dropdown filters by id (row.networkIds - the names-index's
  // network_ids, available immediately rather than waiting on per-row
  // hydration - see pages/group/all.vue's `rows` computed), not by matching
  // display text.
  const matchesId = (ids, needle) => !needle || (Array.isArray(ids) && ids.map(String).includes(String(needle)))

  // Tag filter is multi-select (legacy's vue-multiselect :multiple="true" -
  // GroupsTableFilters.vue), so `needle` is an array of tag ids; a row
  // matches if it has any tag in common (legacy's filteredGroups: "Tag in
  // common?" - tagsInCommon.length > 0), not all of them.
  const matchesTags = (ids, needles) =>
    !needles?.length || (Array.isArray(ids) && needles.some((n) => ids.map(String).includes(String(n))))

  return (
    has(row.name, f.name) &&
    has(row.location?.location, f.location) &&
    has(row.location?.country, f.country) &&
    matchesTags(row.tagIds, f.tags) &&
    matchesId(row.networkIds, f.network)
  )
}

const filteredGroups = computed(() => (props.showFilters ? props.groups.filter(matchesFilters) : props.groups))

function sortBy(key) {
  if (sortKey.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortKey.value = key
    sortDesc.value = false
  }
}

function compareNullableNumber(a, b) {
  if (a == null && b == null) return 0
  if (a == null) return 1
  if (b == null) return -1
  return a - b
}

function nextEventTime(row) {
  return row.nextEvent?.start ? new Date(row.nextEvent.start).getTime() : null
}

const sortedGroups = computed(() => {
  const rows = [...filteredGroups.value]
  const dir = sortDesc.value ? -1 : 1

  rows.sort((a, b) => {
    let result

    switch (sortKey.value) {
      case 'location':
        result = (a.location?.location || '').localeCompare(b.location?.location || '')
        break
      case 'hosts':
        result = compareNullableNumber(a.hosts, b.hosts)
        break
      case 'restarters':
        result = compareNullableNumber(a.restarters, b.restarters)
        break
      case 'next_event':
        result = compareNullableNumber(nextEventTime(a), nextEventTime(b))
        break
      default:
        result = (a.name || '').localeCompare(b.name || '')
    }

    return result * dir
  })

  return rows
})

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, { day: 'numeric', month: 'short', year: 'numeric' })
}

// Current sort state, announced from the <th> where ARIA honours it.
const sortAria = (key) => sortAriaValue(key, sortKey.value, sortDesc.value)

// What a click will do next, announced on the control itself.
const sortHint = (key) => t(sortHintKey(key, sortKey.value, sortDesc.value))

function sortCaretClass(key) {
  // Every column is sortable. Legacy (bootstrap-vue's b-table) shows a
  // paired up/down arrow glyph next to every sortable header, darkening
  // whichever direction is currently active; render the same paired
  // up+down carets here (the .sort-carets/.sort-caret markup/styles below)
  // rather than a single directional triangle, and darken the resolved
  // direction - a CSS caret rather than a unicode character embedded in
  // the (icon-only, gap #4) header label.
  if (sortKey.value !== key) return ''
  return sortDesc.value ? 'sort-carets--desc' : 'sort-carets--asc'
}
</script>

<template>
  <div data-testid="groups-table">
    <GroupsTableFilters
      v-if="showFilters"
      :groups="groups"
      :show-tags="showTags"
      @update:filters="filters = $event"
    />
    <table class="table groups-table">
      <thead>
        <!-- Legacy's b-table hides the whole header row below md
             (thead-tr-class="d-none d-md-table-row") rather than
             collapsing individual headers - below md there's no sortable
             header row at all, only the row-per-group list. -->
        <tr class="groups-table-head-row">
          <th class="groups-table-photo-col">
            <span class="visually-hidden">{{ t('groups.group_image') }}</span>
          </th>
          <th :aria-sort="sortAria('name')">
            <button
              type="button"
              class="sort-header"
              :aria-label="t('groups.groups_name')"
              data-testid="groups-table-sort-name"
              @click="sortBy('name')"
            >
              <span class="visually-hidden">{{ sortHint('name') }}</span>
              <img src="/icons/group_name_ico.svg" alt="" class="col-icon">
              <span class="sort-carets" :class="sortCaretClass('name')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </button>
          </th>
          <!-- Location is not a sortable b-table field in develop (fields:
               [{ key: 'location', label: 'Location', tdClass: "hidecell",
               thClass: "hidecell" }] has no `sortable: true`, unlike
               group_name/hosts/restarters/next_event) - plain header, no
               sort button/caret/aria-sort. -->
          <th v-if="optionalColumns.location">
            <span class="visually-hidden">{{ t('client.groups.column_location') }}</span>
            <img src="/icons/map_marker_ico.svg" alt="" class="col-icon">
          </th>
          <th v-if="optionalColumns.hosts" :aria-sort="sortAria('hosts')">
            <button
              type="button"
              class="sort-header"
              :aria-label="t('client.groups.column_hosts')"
              data-testid="groups-table-sort-hosts"
              @click="sortBy('hosts')"
            >
              <span class="visually-hidden">{{ sortHint('hosts') }}</span>
              <img src="/icons/user_ico.svg" alt="" class="col-icon col-icon--small">
              <span class="sort-carets" :class="sortCaretClass('hosts')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </button>
          </th>
          <th v-if="optionalColumns.restarters" :aria-sort="sortAria('restarters')">
            <button
              type="button"
              class="sort-header"
              :aria-label="t('client.groups.column_restarters')"
              data-testid="groups-table-sort-restarters"
              @click="sortBy('restarters')"
            >
              <span class="visually-hidden">{{ sortHint('restarters') }}</span>
              <img src="/icons/volunteer_ico-thick.svg" alt="" class="col-icon">
              <span class="sort-carets" :class="sortCaretClass('restarters')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </button>
          </th>
          <th v-if="optionalColumns.next_event" :aria-sort="sortAria('next_event')">
            <button
              type="button"
              class="sort-header"
              :aria-label="t('client.groups.column_next_event')"
              data-testid="groups-table-sort-next_event"
              @click="sortBy('next_event')"
            >
              <span class="visually-hidden">{{ sortHint('next_event') }}</span>
              <img src="/icons/events_ico.svg" alt="" class="col-icon">
              <span class="sort-carets" :class="sortCaretClass('next_event')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </button>
          </th>
          <th v-if="showJoin || approve">
            <span class="visually-hidden">
              {{ approve ? t('networks.moderation.group_requires_moderation') : t('groups.join_group_button') }}
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in sortedGroups"
          :key="row.id"
          :data-testid="`group-row-${row.id}`"
          :class="{ 'group-row-hovered': hoveredId === row.id }"
          @mouseenter="emit('update:hoveredId', row.id)"
          @mouseleave="emit('update:hoveredId', null)"
        >
          <td class="groups-table-photo-col">
            <img :src="imageSrc(row)" alt="" class="group-photo" :data-testid="`group-row-photo-${row.id}`">
          </td>
          <td>
            <NuxtLink :to="`/group/view/${row.id}`" :data-testid="`group-row-link-${row.id}`">
              {{ row.name }}
            </NuxtLink>
            <div>
              <BBadge
                v-if="showRole && roleLabelKey(row.role)"
                :variant="roleVariant(row.role)"
                class="me-1"
                :data-testid="`group-row-role-${row.id}`"
              >
                {{ t(roleLabelKey(row.role)) }}
              </BBadge>
              <BBadge
                v-if="row.archivedAt"
                variant="secondary"
                pill
                :title="t('groups.archived_group_title', { date: row.archivedAt })"
                :data-testid="`group-row-archived-${row.id}`"
              >
                {{ t('groups.archived_group') }}
              </BBadge>
            </div>
            <div v-if="showTags && row.tags && row.tags.length" class="mt-1" :data-testid="`group-row-tags-${row.id}`">
              <BBadge
                v-for="tag in row.tags"
                :key="tag.id"
                variant="secondary"
                class="me-1 tag-badge"
              >
                {{ tag.tag_name ?? tag.name }}
              </BBadge>
            </div>
          </td>
          <td v-if="optionalColumns.location" class="hidecell">
            <template v-if="row.location">
              {{ row.location.location }}
              <span v-if="row.location.country" class="text-muted small">{{ row.location.country }}</span>
            </template>
          </td>
          <td v-if="optionalColumns.hosts" class="hidecell" :data-testid="`group-row-hosts-${row.id}`">
            {{ row.hosts ?? '' }}
          </td>
          <td v-if="optionalColumns.restarters" class="hidecell" :data-testid="`group-row-restarters-${row.id}`">
            {{ row.restarters ?? '' }}
          </td>
          <td v-if="optionalColumns.next_event" class="hidecell" :data-testid="`group-row-next-event-${row.id}`">
            <template v-if="row.nextEvent">{{ dateLabel(row.nextEvent.start) }}</template>
            <template v-else>{{ t('groups.upcoming_none_planned') }}</template>
          </td>
          <td v-if="approve" class="cell-warning p-2">
            <NuxtLink :to="`/group/edit/${row.id}`" :data-testid="`groups-table-moderate-${row.id}`">
              {{ t('networks.moderation.group_requires_moderation') }}
            </NuxtLink>
          </td>
          <td v-else-if="showJoin">
            <GroupJoinButton :group-id="row.id" :group-name="row.name" :is-member="!!row.isMember" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
// Legacy's .hidecell (resources/js/components/GroupsTable.vue's SCSS):
// location/hosts/restarters/next_event are hidden below md and shown as
// table cells at md+, regardless of which optional columns a given page
// has opted into via optionalColumns - that prop controls whether a column
// exists on this page at all (a separate, Nuxt-side data-availability
// concern - docs/nuxt-migration/api-gaps.md), this controls the same
// column's responsiveness once it does. Without this, all opted-in columns
// rendered at every width, overflowing a 390px viewport and running
// together illegibly (mobile parity pass).
.hidecell {
  display: none;

  @media (min-width: 768px) {
    display: table-cell;
  }
}

// Legacy hides the whole header row below md (thead-tr-class="d-none
// d-md-table-row") rather than collapsing individual headers - see the
// template's own comment above this row.
.groups-table-head-row {
  display: none;

  @media (min-width: 768px) {
    display: table-row;
  }
}

.sort-header {
  display: inline-flex;
  align-items: center;
  background: none;
  border: 0;
  padding: 0;
  font-weight: bold;
  cursor: pointer;
}

.col-icon {
  width: 30px;
  height: 30px;
}

.col-icon--small {
  width: 25px;
  height: 25px;
}

// Paired up/down carets, matching legacy's b-table sortable-header icon
// (both arrows always shown; the active direction darkens, the other stays
// muted) rather than a single directional triangle.
.sort-carets {
  display: inline-flex;
  flex-direction: column;
  justify-content: center;
  margin-left: 4px;
  vertical-align: middle;
}

.sort-caret {
  display: block;
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
}

.sort-caret--up {
  border-bottom: 5px solid #adb5bd;
  margin-bottom: 2px;
}

.sort-caret--down {
  border-top: 5px solid #adb5bd;
}

.sort-carets--asc .sort-caret--up {
  border-bottom-color: #000;
}

.sort-carets--desc .sort-caret--down {
  border-top-color: #000;
}

.groups-table-photo-col {
  width: 90px;
}

.group-photo {
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

.group-row-hovered {
  background-color: var(--bs-tertiary-bg, #f8f9fa);
}
</style>
