<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupRole } from '../../composables/useGroupRole.js'
import GroupJoinButton from './GroupJoinButton.vue'

// Sortable groups table shared by /group (mine) and /group/all
// (resources/js/components/GroupsTable.vue is the functional spec - column
// set and sort behaviour, not markup/styling).
//
// Rows are pre-normalised by the page: {id, name, archivedAt, role,
// location: {location, country} | null, hosts, restarters,
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
  showJoin: {
    type: Boolean,
    default: true,
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
})

const emit = defineEmits(['update:hoveredId'])

const { t, locale } = useI18n()
const { roleLabelKey, roleVariant } = useGroupRole()

const sortKey = ref('name')
const sortDesc = ref(false)

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
  const rows = [...props.groups]
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

function sortIndicator(key) {
  if (sortKey.value !== key) return ''
  return sortDesc.value ? '▼' : '▲'
}
</script>

<template>
  <div data-testid="groups-table">
    <table class="table">
      <thead>
        <tr>
          <th>
            <button type="button" class="sort-header" data-testid="groups-table-sort-name" @click="sortBy('name')">
              {{ t('groups.groups_name') }} {{ sortIndicator('name') }}
            </button>
          </th>
          <th v-if="optionalColumns.location">
            <button
              type="button"
              class="sort-header"
              data-testid="groups-table-sort-location"
              @click="sortBy('location')"
            >
              {{ t('client.groups.column_location') }} {{ sortIndicator('location') }}
            </button>
          </th>
          <th v-if="optionalColumns.hosts">
            <button
              type="button"
              class="sort-header"
              data-testid="groups-table-sort-hosts"
              @click="sortBy('hosts')"
            >
              {{ t('client.groups.column_hosts') }} {{ sortIndicator('hosts') }}
            </button>
          </th>
          <th v-if="optionalColumns.restarters">
            <button
              type="button"
              class="sort-header"
              data-testid="groups-table-sort-restarters"
              @click="sortBy('restarters')"
            >
              {{ t('client.groups.column_restarters') }} {{ sortIndicator('restarters') }}
            </button>
          </th>
          <th v-if="optionalColumns.next_event">
            <button
              type="button"
              class="sort-header"
              data-testid="groups-table-sort-next_event"
              @click="sortBy('next_event')"
            >
              {{ t('client.groups.column_next_event') }} {{ sortIndicator('next_event') }}
            </button>
          </th>
          <th v-if="showJoin" />
        </tr>
      </thead>
      <tbody>
        <tr v-if="!sortedGroups.length" data-testid="groups-table-empty">
          <td :colspan="6">
            {{ t('client.groups.no_results') }}
          </td>
        </tr>
        <tr
          v-for="row in sortedGroups"
          :key="row.id"
          :data-testid="`group-row-${row.id}`"
          :class="{ 'group-row-hovered': hoveredId === row.id }"
          @mouseenter="emit('update:hoveredId', row.id)"
          @mouseleave="emit('update:hoveredId', null)"
        >
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
                :data-testid="`group-row-archived-${row.id}`"
              >
                {{ t('groups.archived_group') }}
              </BBadge>
            </div>
          </td>
          <td v-if="optionalColumns.location">
            <template v-if="row.location">
              {{ row.location.location }}
              <span v-if="row.location.country" class="text-muted small">{{ row.location.country }}</span>
            </template>
          </td>
          <td v-if="optionalColumns.hosts" :data-testid="`group-row-hosts-${row.id}`">
            {{ row.hosts ?? '' }}
          </td>
          <td v-if="optionalColumns.restarters" :data-testid="`group-row-restarters-${row.id}`">
            {{ row.restarters ?? '' }}
          </td>
          <td v-if="optionalColumns.next_event" :data-testid="`group-row-next-event-${row.id}`">
            <template v-if="row.nextEvent">{{ dateLabel(row.nextEvent.start) }}</template>
            <template v-else>{{ t('groups.upcoming_none_planned') }}</template>
          </td>
          <td v-if="showJoin">
            <GroupJoinButton :group-id="row.id" :group-name="row.name" :is-member="!!row.isMember" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
.sort-header {
  background: none;
  border: 0;
  padding: 0;
  font-weight: bold;
  cursor: pointer;
}

.group-row-hovered {
  background-color: var(--bs-tertiary-bg, #f8f9fa);
}
</style>
