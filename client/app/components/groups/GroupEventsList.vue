<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// GET /api/v2/groups/{id}/events (API\GroupController::getEventsForGroupv2
// - EventSummary[], split into upcoming/past client-side). Functional spec:
// group/view.blade.php's GroupEvents.vue + GroupEventScrollTable.vue - a
// sortable table (not a bare title+date list) with "Add new event"/"Export
// event list"/calendar-subscribe controls (parity gap 5).
//
// EventSummary only carries {id, start, end, title, location, group,
// timezone, online, ...} - none of GroupEventScrollTable's per-event stats
// columns (invited/volunteers/participants/waste/co2/fixed/repairable/dead),
// which come from a separate events/getStats Vuex lookup develop's Blade
// props seed up front and this v2 endpoint has no equivalent of. Rather than
// fabricate those numbers, this table sticks to date/title/location -
// the closest faithful match the available API data supports.
const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  groupId: {
    type: Number,
    required: true,
  },
  groupName: {
    type: String,
    default: '',
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  showCalendar: {
    type: Boolean,
    default: false,
  },
})

const { t, locale } = useI18n()

const activeTab = ref('upcoming')
const sortDesc = ref(false)
const showCalendarModal = ref(false)

const upcoming = computed(() =>
  props.events
    .filter((e) => new Date(e.end || e.start) >= new Date())
    .sort((a, b) => new Date(a.start) - new Date(b.start))
)

const past = computed(() =>
  props.events
    .filter((e) => new Date(e.end || e.start) < new Date())
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)

const rows = computed(() => {
  const base = activeTab.value === 'upcoming' ? upcoming.value : past.value
  const sorted = [...base]
  sorted.sort((a, b) => {
    const cmp = new Date(a.start) - new Date(b.start)
    return sortDesc.value ? -cmp : cmp
  })
  return sorted
})

function toggleSort() {
  sortDesc.value = !sortDesc.value
}

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const calendarUrl = computed(() => `/calendar/group/${props.groupId}`)

function copyCalendarUrl() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(calendarUrl.value)
  }
}
</script>

<template>
  <div data-testid="group-events-list">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
      <!-- GroupEvents.vue's title slot: "{group name} Events" plus the
           calendar-subscribe button sit together to the left of the
           export/add-event actions (parity diff: heading text + icon). -->
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h2 class="mb-0" data-testid="group-events-heading">{{ groupName }} {{ t('events.events') }}</h2>
        <BButton
          v-if="showCalendar"
          variant="primary"
          data-testid="group-events-calendar"
          @click="showCalendarModal = true"
        >
          <img :src="'/images/subs_cal_ico.svg'" alt="" width="20" height="20">
        </BButton>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a
          v-if="canedit"
          :href="`/export/groups/${groupId}/events`"
          class="btn btn-primary text-nowrap"
          data-testid="group-events-export"
        >
          {{ t('groups.export_event_list') }}
        </a>
        <NuxtLink
          v-if="canedit"
          :to="`/party/create/${groupId}`"
          class="btn btn-primary text-nowrap"
          data-testid="group-events-add"
        >
          {{ t('events.add_new_event') }}
        </NuxtLink>
      </div>
    </div>

    <div v-if="loading" data-testid="group-events-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <!-- GroupEvents.vue's <b-tabs> sit in a bordered, drop-shadowed box with
         UPPERCASE tab labels - the same "brutalist" panel chrome as
         .box-shadow-offset/.panel elsewhere, not bare underlined tabs. -->
    <div v-else class="events-tabs" data-testid="group-events-tabs">
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'upcoming' }"
            data-testid="group-events-tab-upcoming"
            @click="activeTab = 'upcoming'"
          >
            {{ t('groups.upcoming_active') }} ({{ upcoming.length }})
          </button>
        </li>
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'past' }"
            data-testid="group-events-tab-past"
            @click="activeTab = 'past'"
          >
            {{ t('groups.past') }} ({{ past.length }})
          </button>
        </li>
      </ul>

      <div v-if="activeTab === 'upcoming'" data-testid="group-events-panel-upcoming" class="events-tabs__content">
        <p v-if="!upcoming.length" data-testid="group-events-empty-upcoming">
          {{ t('groups.no_upcoming_events') }}
        </p>
        <table v-else class="table" data-testid="group-events-table-upcoming">
          <thead>
            <tr>
              <th scope="col" class="sortable" role="button" @click="toggleSort">
                <span>{{ t('client.groups.column_event') }}</span>
                <span class="sort-indicator">{{ sortDesc ? '▼' : '▲' }}</span>
              </th>
              <th scope="col">{{ t('client.groups.column_location') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in rows" :key="event.id" :data-testid="`group-event-${event.id}`">
              <td>
                <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`group-event-link-${event.id}`">
                  {{ event.title }}
                </NuxtLink>
                <div class="small text-muted">{{ dateLabel(event.start) }}</div>
              </td>
              <td>{{ event.location }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else data-testid="group-events-panel-past" class="events-tabs__content">
        <p v-if="!past.length" data-testid="group-events-empty-past">
          {{ t('groups.no_past_events') }}
        </p>
        <table v-else class="table" data-testid="group-events-table-past">
          <thead>
            <tr>
              <th scope="col" class="sortable" role="button" @click="toggleSort">
                <span>{{ t('client.groups.column_event') }}</span>
                <span class="sort-indicator">{{ sortDesc ? '▼' : '▲' }}</span>
              </th>
              <th scope="col">{{ t('client.groups.column_location') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in rows" :key="event.id" :data-testid="`group-event-${event.id}`">
              <td>
                <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`group-event-link-${event.id}`">
                  {{ event.title }}
                </NuxtLink>
                <div class="small text-muted">{{ dateLabel(event.start) }}</div>
              </td>
              <td>{{ event.location }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BModal
      :model-value="showCalendarModal"
      data-testid="group-events-calendar-modal"
      :title="t('groups.calendar_copy_title')"
      no-footer
      @hide="showCalendarModal = false"
    >
      <p>{{ t('groups.calendar_copy_description', { group: groupName }) }}</p>
      <div class="input-group">
        <input type="text" class="form-control" readonly :value="calendarUrl" data-testid="group-events-calendar-url">
        <BButton variant="primary" @click="copyCalendarUrl">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped>
.sortable {
  cursor: pointer;
  user-select: none;
}

.sort-indicator {
  font-size: 0.7em;
  margin-left: 0.25rem;
}

/* GroupEvents.vue's tabs box: 1px black border + the sitewide 6px hard
   drop-shadow (.box-shadow-offset in _type.scss), tab row recoloured from
   Bootstrap's default grey to black, active tab getting a 5px black top
   bar (::before) so it "pops" above the inactive one - ported from
   .nav-tabs-block's active-tab treatment in _tabs.scss. */
.events-tabs {
  background: #fff;
  border: 1px solid #222;
  box-shadow: 6px 6px 0 0 #222;
}

.events-tabs .nav-tabs {
  border-bottom: 0;
  flex-wrap: nowrap;
}

.events-tabs .nav-item {
  flex: 1 1 0;
}

.events-tabs .nav-item:not(:last-child) .nav-link {
  border-right: 1px solid #222;
}

.events-tabs .nav-link {
  position: relative;
  width: 100%;
  border: 0;
  border-bottom: 1px solid #222;
  border-radius: 0;
  background: #f9f9f9;
  color: #555;
  font-weight: 700;
  text-align: center;
  text-transform: uppercase;
  padding: 0.9rem 1rem;
}

.events-tabs .nav-link.active {
  background: #fff;
  color: #222;
  border-bottom-color: #fff;
}

.events-tabs .nav-link.active::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: #222;
}

.events-tabs__content {
  padding: 1rem 1.25rem 1.25rem;
}
</style>
