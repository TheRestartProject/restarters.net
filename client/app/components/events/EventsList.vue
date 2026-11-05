<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { sortAriaValue, sortHintKey } from '~/composables/useSortAria.js'
import EventCard from './EventCard.vue'

// Renders a bucket of events (already filtered/sorted by the caller - see
// pages/party/{index,all,all-past}.vue, which use the pure helpers in
// composables/useEventComputed.js to bucket the single myEvents list) as a
// data table, with loading/empty states. Gap 1: develop renders this as a
// b-table (GroupEventScrollTable.vue) with icon-labelled stat columns, not a
// card list - this is that table, one <tr> per event via EventCard.vue.
// Kept deliberately dumb otherwise: no tabs, no fetching - GroupEventsList
// .vue owns that for the group-view tab, this owns just the list rendering
// shared across the three /party pages.
const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: '',
  },
  hostedGroupIds: {
    type: Array,
    default: () => [],
  },
  // Gap 18: EventCard.vue's no-devices CTA needs to know canedit, which is
  // isAdmin OR hosting-that-row's-group - hosting is already computed
  // per-row below from hostedGroupIds, so this only needs the admin half.
  isAdmin: {
    type: Boolean,
    default: false,
  },
  // Column set: false = invited/volunteers (upcoming/nearby/all buckets),
  // true = participants/volunteers/waste/co2/fixed/repairable/dead
  // (finished buckets) - see GroupEventScrollTable.vue's identically-named
  // prop. A single table only ever renders one bucket, so this is set once
  // per EventsList instance rather than per row.
  past: {
    type: Boolean,
    default: false,
  },
  // GroupEventScrollTable.vue's b-table made the date, title and number
  // columns sortable. The group page's events list wants that; the /party
  // pages never had it.
  sortable: {
    type: Boolean,
    default: false,
  },
  // GroupEventScrollTable.vue's addGroupName: the group name under each title.
  // Off on a group's own page, where every row is that group.
  showGroup: {
    type: Boolean,
    default: true,
  },
})

defineEmits(['attendance'])

const { t } = useI18n()

// Header icons for the number columns, in GroupEventScrollTable.vue's
// fields() order for each bucket.
const numberColumns = computed(() =>
  props.past
    ? [
        { key: 'participants', icon: '/images/participants.svg', title: t('groups.participants_attended') },
        { key: 'volunteers', icon: '/icons/volunteer_ico.svg', title: t('groups.volunteers_attended') },
        { key: 'waste', icon: '/images/trash.svg', title: t('events.stat-7') },
        { key: 'co2', icon: '/images/cloud-empty.svg', title: t('events.stat-6') },
        { key: 'fixed', icon: '/images/fixed.svg', title: t('groups.fixed_items') },
        { key: 'repairable', icon: '/images/repairable_ico.svg', title: t('groups.repairable_items') },
        { key: 'dead', icon: '/images/dead_ico.svg', title: t('groups.end_of_life_items') },
      ]
    : [
        { key: 'invited', icon: '/images/mail_ico.svg', title: t('groups.volunteers_invited') },
        { key: 'volunteers', icon: '/images/participants.svg', title: t('groups.volunteers_confirmed') },
      ]
)

// How each sortable column reads its value; null sorts last either way
// (b-table's sort-null-last). Title is venue-or-location, as EventTitle.vue.
const SORT_VALUES = {
  date: (e) => new Date(e.start).getTime(),
  title: (e) => String(e.title || '').toLowerCase(),
  invited: (e) => e.stats?.invited ?? null,
  volunteers: (e) => e.stats?.volunteers ?? null,
  participants: (e) => e.stats?.participants ?? null,
  waste: (e) => e.stats?.waste_total ?? null,
  co2: (e) => e.stats?.co2_total ?? null,
  fixed: (e) => e.stats?.fixed_devices ?? null,
  repairable: (e) => e.stats?.repairable_devices ?? null,
  dead: (e) => e.stats?.dead_devices ?? null,
}

// Starts sorted by date, the way the caller already orders each bucket:
// upcoming soonest first, past most recent first.
const sortKey = ref('date')
const sortDesc = ref(props.past)

const rows = computed(() => {
  if (!props.sortable) {
    return props.events
  }

  const read = SORT_VALUES[sortKey.value]

  return [...props.events].sort((a, b) => {
    const av = read(a)
    const bv = read(b)

    if (av === null || bv === null) {
      return (av === null) - (bv === null)
    }

    const cmp = typeof av === 'string' ? av.localeCompare(bv) : av - bv

    return sortDesc.value ? -cmp : cmp
  })
})

// First click sorts ascending on a new column; clicking the active one flips
// it, matching b-table.
function toggleSort(key) {
  if (sortKey.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortKey.value = key
    sortDesc.value = false
  }
}

function sortAttrs(key) {
  if (!props.sortable) {
    return {}
  }

  return {
    role: 'button',
    tabindex: 0,
    class: 'sortable',
    'aria-sort': sortAriaValue(key, sortKey.value, sortDesc.value),
    'data-testid': `events-list-sort-${key}`,
    onClick: () => toggleSort(key),
    onKeydown: (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        toggleSort(key)
      }
    },
  }
}

// The paired up/down carets b-table showed on every sortable header, with the
// active direction darkened (same markup and styles as GroupsTable.vue).
function sortCaretClass(key) {
  if (sortKey.value !== key) {
    return ''
  }

  return sortDesc.value ? 'sort-carets--desc' : 'sort-carets--asc'
}

function sortHint(key) {
  return t(sortHintKey(key, sortKey.value, sortDesc.value))
}
</script>

<template>
  <div data-testid="events-list">
    <div v-if="loading" data-testid="events-list-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <!-- Rendered as HTML: several of these strings carry markup in the lang
         files - events.no_location is
         "<b>You do not currently have a town/city set. You can set one in
         <a href=\"/profile/edit\">your profile</a>.</b>" - and interpolating
         it printed the tags as literal text on /party. The strings come from
         our own lang files, not from user input. -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <p v-else-if="!events.length" data-testid="events-list-empty" v-html="emptyMessage" />

    <!-- GroupEventScrollTable.vue's b-table: sticky-header="50vh", so a long
         list scrolls inside its box under a header that stays in view. -->
    <div v-else class="table-responsive events-scroll mt-2 ps-0 ps-md-3 pe-0 pe-md-3 pb-2 mb-2" data-testid="events-list-scroll">
      <table class="table align-middle m-0" data-testid="events-list-items">
        <thead>
          <tr>
            <th scope="col" />
            <!-- Gap 1: date_long column header - GroupEventScrollTable.vue's
                 head(date_long) shows a clock icon rather than text. -->
            <th scope="col" v-bind="sortAttrs('date')">
              <img :src="'/images/clock.svg'" alt="" class="col-icon mt-3 ms-3">
              <span v-if="sortable" class="visually-hidden">{{ sortHint('date') }}</span>
              <span v-if="sortable" class="sort-carets" :class="sortCaretClass('date')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </th>
            <th scope="col" v-bind="sortAttrs('title')">
              <span v-if="sortable" class="visually-hidden">{{ sortHint('title') }}</span>
              <span v-if="sortable" class="sort-carets" :class="sortCaretClass('title')" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </th>
            <th
              v-for="column in numberColumns"
              :key="column.key"
              scope="col"
              class="text-center"
              v-bind="sortAttrs(column.key)"
            >
              <img :src="column.icon" alt="" class="col-icon mt-3" :title="column.title">
              <span v-if="sortable" class="visually-hidden">{{ sortHint(column.key) }}</span>
              <span v-if="sortable" class="sort-carets" :class="sortCaretClass(column.key)" aria-hidden="true">
                <span class="sort-caret sort-caret--up" />
                <span class="sort-caret sort-caret--down" />
              </span>
            </th>
            <th scope="col" class="d-none d-md-table-cell" />
          </tr>
        </thead>
        <tbody>
          <EventCard
            v-for="event in rows"
            :key="event.id"
            :event="event"
            :past="past"
            :show-group="showGroup"
            :hosting="!!event.group && hostedGroupIds.includes(event.group.id)"
            :canedit="isAdmin || (!!event.group && hostedGroupIds.includes(event.group.id))"
            @attendance="$emit('attendance', $event)"
          />
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.events-scroll {
  max-height: 50vh;
  overflow-y: auto;
}

/* bootstrap-vue's sticky header cells are white, so rows scroll under them. */
.events-scroll thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #fff;
}

.col-icon {
  width: 30px;
  height: 30px;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

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
</style>
