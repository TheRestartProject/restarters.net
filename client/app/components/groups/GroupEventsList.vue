<script setup>
import LoadingPlaceholder from '~/components/LoadingPlaceholder.vue'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useAuth } from '~/composables/useAuth.js'
import { useClipboard } from '~/composables/useClipboard.js'
import EventsList from '~/components/events/EventsList.vue'

// GET /api/v2/groups/{id}/events (API\GroupController::getEventsForGroupv2
// - PartySummary[], split into upcoming/past client-side). Functional spec:
// group/view.blade.php's GroupEvents.vue -> GroupEventsTab.vue ->
// GroupEventScrollTable.vue: upcoming/past tabs, each a sortable table of
// date box, long date + time + timezone, title, then invited/volunteers and
// "You're going!"/RSVP (upcoming) or the per-event stats (past). That table
// is EventsList.vue, as on /party, without the group name (addGroupName is
// off on a group's own page).
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

const { t } = useI18n()

// The group name starts the heading, so "events" is lower case, in sentence case like the rest of the page
// (develop #946).
const eventsWord = computed(() => {
  const word = t('events.events')

  return word.charAt(0).toLowerCase() + word.slice(1)
})

const activeTab = ref('upcoming')
const showCalendarModal = ref(false)
const calendarCopied = ref(false)

const groupsStore = useGroupsStore()
const { user } = useAuth()
const { copy } = useClipboard()

// The buckets in GroupEvents.vue's starting order: upcoming soonest first,
// past most recent first. EventsList sorts within that when a header is
// clicked.
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

// RSVPing from this list: the events store doesn't hold the group's list.
function onAttendance({ id, attending }) {
  groupsStore.setEventAttending(id, attending)
}

// develop's view.blade.php passes url("/calendar/group/{id}") - an absolute
// URL, which is what a calendar app needs to subscribe to.
const calendarUrl = computed(() => new URL(`/calendar/group/${props.groupId}`, window.location.origin).href)

function copyCalendarUrl() {
  copy(calendarUrl.value)
  calendarCopied.value = true
}

function openCalendar() {
  calendarCopied.value = false
  showCalendarModal.value = true
}
</script>

<template>
  <div data-testid="group-events-list">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
      <!-- GroupEvents.vue's title slot: "{group name} Events" plus the
           calendar-subscribe button sit together to the left of the
           export/add-event actions (parity diff: heading text + icon). -->
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h2 class="mb-0" data-testid="group-events-heading">{{ groupName }} {{ eventsWord }}</h2>
        <BButton
          v-if="showCalendar"
          variant="primary"
          data-testid="group-events-calendar"
          @click="openCalendar"
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
      <LoadingPlaceholder height="3rem" />
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
        <!-- canedit is develop's group-level canedit (host of this group or
             admin), which is what EventCard needs for its no-devices prompt;
             no hosting badges, as GroupEventScrollTable has none. -->
        <EventsList
          :events="upcoming"
          :empty-message="t('groups.no_upcoming_events')"
          :is-admin="canedit"
          sortable
          :show-group="false"
          @attendance="onAttendance"
        />
      </div>

      <div v-else data-testid="group-events-panel-past" class="events-tabs__content">
        <EventsList
          :events="past"
          past
          :empty-message="t('groups.no_past_events')"
          :is-admin="canedit"
          sortable
          :show-group="false"
          @attendance="onAttendance"
        />
      </div>
    </div>

    <BModal
      :model-value="showCalendarModal"
      size="lg"
      data-testid="group-events-calendar-modal"
      :title="t('groups.calendar_copy_title')"
      no-footer
      @hide="showCalendarModal = false"
    >
      <p>{{ t('groups.calendar_copy_description', { group: groupName }) }}</p>
      <div class="input-group">
        <input type="text" class="form-control" readonly :value="calendarUrl" :aria-label="t('profile.calendars.group_calendars')" data-testid="group-events-calendar-url">
        <BButton variant="primary" data-testid="group-events-calendar-copy" @click="copyCalendarUrl">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>
      <BAlert v-if="calendarCopied" :model-value="true" variant="info" class="mt-2" data-testid="group-events-calendar-copied">
        {{ t('events.copied_to_clipboard') }}
      </BAlert>
      <!-- CalendarAddModal.vue's two links: the Talk how-to, and the
           calendar-edit-url develop passed (the profile's calendar links). -->
      <div class="d-flex justify-content-between flex-wrap mt-3">
        <a
          href="https://talk.restarters.net/t/fixometer-how-to-add-repair-events-to-your-calendar-application/1770"
          target="_blank"
          rel="noopener"
          data-testid="group-events-calendar-find-out-more"
        >
          {{ t('calendars.find_out_more') }}
        </a>
        <NuxtLink v-if="user" :to="`/profile/edit/${user.id}`" data-testid="group-events-calendar-see-all">
          {{ t('calendars.see_all_calendars') }}
        </NuxtLink>
      </div>
    </BModal>
  </div>
</template>
