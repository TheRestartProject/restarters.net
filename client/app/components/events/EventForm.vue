<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment-timezone'
import { useEventsStore } from '../../stores/events.js'
import { useGroupsStore } from '../../stores/groups.js'
import { eventStartLocal, eventEndLocal } from '../../composables/useEventComputed.js'
import RichTextEditor from '../forms/RichTextEditor.vue'
import LocationPicker from '../forms/LocationPicker.vue'
import DatePicker from 'vue-datepicker-next'
import 'vue-datepicker-next/index.css'
// Admin-only network_data custom-field editor - genuinely generic (keyed
// purely off modelValue, no group-specific behaviour), so it's reused as-is
// rather than duplicated under events/ (findings/parity-v2 precedent: this
// is the same NetworkData.vue/NetworkDataField.vue port GroupForm.vue
// already uses for its own admin-only card).
import GroupNetworkData from '../groups/GroupNetworkData.vue'

// Shared by /party/create/[[group_id]], /party/edit/[id] and
// /party/duplicate/[id] (design.md §6.2 section 4 C4 task brief).
// Functional spec: resources/views/events/create.blade.php + edit.blade.php,
// resources/js/components/EventAddEdit.vue + EventAddEditPage.vue +
// mixins/event.js. Field names in the submitted payload match
// EventController::validateEventParams exactly (groupid/start/end/title/
// description/location/timezone/online/link/network_data, +moderate on
// edit) - createEventv2/updateEventv2 read $request->input(...) directly,
// so extra/renamed keys are silently ignored, not rejected. Neither
// endpoint reads lat/lng from the request at all - the server geocodes
// `location` itself (App\Helpers\Geocoder), matching the legacy Vuex
// store's create/edit payloads, which never sent lat/lng either.
//
// Scope cuts vs the legacy form (documented here, not in api-gaps.md -
// these are UI simplifications, not missing endpoints, mirroring
// GroupForm.vue's precedent):
//  - The event-group multiselect becomes a plain <select> (vue-multiselect
//    isn't installed for this migration - design.md §2 only lists it for
//    the group/tag pickers already ported in B6).
//  - `duplicateFrom`'s image-upload semantics don't apply (legacy's create
//    form is also the duplicate form and has no image tab either - the
//    dropzone only ever lived in edit.blade.php, so TusImageUpload for
//    events lives in pages/party/edit/[id].vue only, matching legacy 1:1).
//
// The admin-only "event-admin" card (gear icon, EventAddEdit.vue's
// `v-if="canApprove"` b-card - note this is gated on the *page-level*
// canApprove prop, which legacy sets independent of create-vs-edit, unlike
// the moderation `canApprove` computed below which is edit-only) is ported
// via GroupNetworkData, visible whenever `isAdmin` is true, and contains
// only the network_data field editor. Legacy's events form has no
// *visible* per-event timezone control at all (only GroupAddEdit.vue's
// group-level one) - EventAddEdit.vue's `this.timezone` is never assigned,
// so it submits `timezone: undefined` (dropped by JSON.stringify), and the
// event simply inherits the group's timezone via Party::getTimezoneAttribute
// falling back to `$this->theGroup->timezone`. An earlier version of this
// form exposed an editable timezone override here (findings/parity-v2/
// events.md #5 flagged the unconditional version as a bug, then a later
// pass admin-gated it instead of removing it - also wrong, per the
// standing parity-with-develop instruction). `form.timezone` itself is
// kept - auto-populated from the selected group below and still sent in
// the payload - so the event's stored timezone matches what legacy's
// server-side fallback resolves to; only the *visible, editable* control is
// gone.
const props = defineProps({
  eventId: {
    type: Number,
    default: null,
  },
  initialEvent: {
    type: Object,
    default: null,
  },
  // True when initialEvent is the *source* event being duplicated, not the
  // event actually being edited (eventId is null in that case). Mirrors
  // EventAddEdit.vue's created() hook: every field is prefilled from
  // duplicateFrom EXCEPT the date - "we deliberately don't set the date
  // above, because we don't want it set for event duplication."
  isDuplicate: {
    type: Boolean,
    default: false,
  },
  // Group id to preselect when arriving via /party/create/{group_id}
  // (PartyController::create's $group_id param).
  initialGroupId: {
    type: Number,
    default: null,
  },
  // Options for the group <select>, only used in create/duplicate mode -
  // the page resolves these (GET /api/v2/users/me/groups filtered to
  // role===HOST, or every group for an admin) since it needs the same list
  // to decide the "cantcreate" gate before it even renders this component.
  groups: {
    type: Array,
    default: () => [],
  },
  // Administrator: legacy create.blade.php/edit.blade.php pass
  // Fixometer::hasRole($user,'Administrator') (plus a NetworkCoordinator
  // branch this client can't check - see docs/nuxt-migration/api-gaps.md
  // Phase C, same safe-false-negative approximation as party/view/[id].vue's
  // canedit) as canApprove, and separately use it to source "every group"
  // instead of "groups I host" for the picker. Both derive from this one
  // prop, same as GroupForm.vue's isAdmin.
  isAdmin: {
    type: Boolean,
    default: false,
  },
  // EventAddEditPage.vue:104 - set once the create succeeds, showing a green
  // confirmation on the (now) edit form and suppressing the "what happens when
  // you submit" notice, which no longer applies. develop keeps one component
  // mounted across create->edit and swaps the URL with history.replaceState;
  // this client really does navigate, so the create/duplicate pages hand the
  // flag over through the events store (see justCreatedId there).
  justCreated: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['created', 'updated'])

const { t } = useI18n()
const eventsStore = useEventsStore()
const groupsStore = useGroupsStore()

const creating = computed(() => !props.eventId)
const canApprove = computed(() => !creating.value && props.isAdmin)
// Gates the admin-only card itself - independent of creating/editing,
// unlike `canApprove` above (see the class doc comment).
const showAdminOnly = computed(() => props.isAdmin)

const form = reactive({
  idgroups: props.initialGroupId || props.initialEvent?.group?.id || null,
  venue: props.initialEvent?.title || '',
  link: props.initialEvent?.link || '',
  description: props.initialEvent?.description || '',
  online: !!props.initialEvent?.online,
  location: props.initialEvent?.location || '',
  timezone: props.initialEvent?.timezone || '',
  eventDate: '',
  startTime: props.initialEvent ? eventStartLocal(props.initialEvent) || '' : '',
  endTime: props.initialEvent ? eventEndLocal(props.initialEvent) || '' : '',
  moderate: '',
})

// Legacy only sets the date when actually editing, never when duplicating
// (see the class doc comment above) - `creating` is true for both create
// and duplicate, so this needs isDuplicate specifically, not `!creating`.
if (props.initialEvent && !props.isDuplicate) {
  form.eventDate = moment.tz(props.initialEvent.start, props.initialEvent.timezone).format('YYYY-MM-DD')
}

// Editable via GroupNetworkData in the admin-only card; a plain object copy
// (not the prop reference) so edits here never mutate initialEvent.
const networkData = ref({ ...(props.initialEvent?.network_data || {}) })
const approved = ref(!!props.initialEvent?.approved)
const eventGroupName = computed(() => props.initialEvent?.group?.name || '')

const groupOptionsSorted = computed(() => [...props.groups].sort((a, b) => a.name.localeCompare(b.name)))

const selectedGroupDetail = computed(() => groupsStore.details[form.idgroups] || null)
const groupLocationText = computed(() => selectedGroupDetail.value?.location?.location || null)

// EventAddEdit.vue:258 - `this.group ? this.group.auto_approve : false`.
// A previous comment here claimed auto_approve wasn't exposed by the API and
// used that to justify always showing the non-autoapproved copy; it is in fact
// exposed (App\Http\Resources\Group:384) and means exactly what develop's does
// (App\Group::getAutoApproveAttribute - true iff EVERY network the group
// belongs to auto-approves events). The watcher below fetches the selected
// group's detail immediately on mount, in edit mode too, so this resolves
// without any extra request.
const autoApprove = computed(() => !!selectedGroupDetail.value?.auto_approve)

const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

// EventController::validateEventParams's 422 error keys are the *payload*
// field names (groupid/title/start/end/...), which don't all match this
// form's own field names (idgroups/venue/startTime/endTime/...) - map them
// so a server-side error highlights the same input a client-side one
// would.
const SERVER_FIELD_MAP = { groupid: 'idgroups', title: 'venue', start: 'startTime', end: 'endTime' }

function mapServerErrors(errors) {
  const mapped = {}
  for (const [key, value] of Object.entries(errors || {})) {
    mapped[SERVER_FIELD_MAP[key] || key] = value
  }
  return mapped
}

onMounted(() => {
  // "If only one group, default to that" (EventAddEdit.vue's created()).
  if (creating.value && !form.idgroups && groupOptionsSorted.value.length === 1) {
    form.idgroups = groupOptionsSorted.value[0].id
  }
})

// Fetches the selected group's full record (for its timezone default and
// the "use group location" shortcut - .event-address's useGroup() in
// VenueAddress.vue) whenever the selection changes, including the initial
// mount. There's no visible timezone control for the user to have typed
// into (see the class doc comment), so the only thing worth protecting is
// an edit/duplicate prefill that already arrived with a value: apply the
// group's timezone on a live user-driven change (oldId !== undefined -
// false on the immediate initial call), or whenever the field is still
// empty.
watch(
  () => form.idgroups,
  async (id, oldId) => {
    if (!id) return

    const detail = await groupsStore.fetchDetails(id)
    const isUserChange = oldId !== undefined

    if (detail?.timezone && (isUserChange || !form.timezone)) {
      form.timezone = detail.timezone
    }
  },
  { immediate: true }
)

function useGroupLocation() {
  if (groupLocationText.value) {
    form.location = groupLocationText.value
  }
}

// Mirrors EventTimeRangePicker.vue's changeEndTime(): when the start time
// changes, bump the end time to 3 hours later (if there's room left in the
// day) so the field isn't left empty/inconsistent by default.
function onStartTimeChange() {
  if (!form.startTime) return

  const [hoursStr, minsStr] = form.startTime.split(':')
  const hours = parseInt(hoursStr, 10)

  if (!form.endTime || form.endTime <= form.startTime) {
    if (hours < 21) {
      const endHours = String(hours + 3).padStart(2, '0')
      form.endTime = `${endHours}:${minsStr}`
    } else {
      form.endTime = form.startTime
    }
  }
}

// Combines the date + a local "HH:mm" time in the given timezone into the
// UTC ISO8601 string the server's date_format validation expects (matches
// EventAddEdit.vue's eventStartUtc/eventEndUtc computeds, incl. the
// milliseconds strip).
function toUtcIso(date, time, timezone) {
  if (!date || !time) return null

  const zone = timezone || moment.tz.guess()
  const m = moment.tz(`${date} ${time}`, zone)

  return m.isValid() ? m.toISOString().replace(/\.\d+Z$/, 'Z') : null
}

function validate() {
  const errors = {}

  if (creating.value && !form.idgroups) {
    errors.idgroups = [t('client.events.group_required')]
  }
  if (!form.venue.trim()) {
    errors.venue = [t('client.events.title_required')]
  }
  if (!form.description || !form.description.trim()) {
    errors.description = [t('client.events.description_required')]
  }
  if (!form.online && !form.location.trim()) {
    errors.location = [t('events.validate_location')]
  }
  if (!form.eventDate) {
    errors.eventDate = [t('client.events.date_required')]
  }
  if (!form.startTime) {
    errors.startTime = [t('client.events.start_required')]
  }
  if (!form.endTime) {
    errors.endTime = [t('client.events.end_required')]
  }
  if (form.eventDate && form.startTime && form.endTime) {
    const start = toUtcIso(form.eventDate, form.startTime, form.timezone)
    const end = toUtcIso(form.eventDate, form.endTime, form.timezone)
    if (start && end && end <= start) {
      errors.endTime = [t('client.events.end_before_start')]
    }
  }
  if (form.link && !/^https?:\/\/.+/i.test(form.link)) {
    errors.link = [t('client.events.link_invalid')]
  }

  return errors
}

async function submit() {
  generalError.value = ''
  fieldErrors.value = {}

  const errors = validate()
  if (Object.keys(errors).length) {
    fieldErrors.value = errors
    return
  }

  submitting.value = true

  const payload = {
    start: toUtcIso(form.eventDate, form.startTime, form.timezone),
    end: toUtcIso(form.eventDate, form.endTime, form.timezone),
    title: form.venue,
    description: form.description,
    location: form.location || null,
    online: form.online,
    link: form.link || null,
    timezone: form.timezone || null,
    // createEventv2 accepts network_data too (validateEventParams reads it
    // unconditionally regardless of the $create flag), and legacy's create
    // dispatch always sends it (defaulting to {} when the admin-only card
    // never rendered) - so, unlike the groupid/moderate split below, this
    // isn't create-vs-edit conditional.
    network_data: JSON.stringify(networkData.value),
  }

  if (creating.value) {
    payload.groupid = form.idgroups
  } else if (canApprove.value && !approved.value && form.moderate === 'approve') {
    payload.moderate = 'approve'
  }

  try {
    if (creating.value) {
      const id = await eventsStore.createEvent(payload)
      emit('created', id)
    } else {
      const id = await eventsStore.updateEvent(props.eventId, payload)
      approved.value = approved.value || form.moderate === 'approve'
      emit('updated', id)
    }
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = mapServerErrors(err.data?.errors)
    }
    generalError.value = creating.value ? t('events.create_failed') : t('events.edit_failed')
  } finally {
    submitting.value = false
  }
}

defineExpose({ submit })
</script>

<template>
  <BForm data-testid="event-form" class="event-form-box" @submit.prevent="submit">
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="event-form-error">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <span v-html="generalError" />
    </BAlert>

    <div class="event-form-layout">
      <div class="event-form-venue d-flex flex-wrap align-items-end gap-3">
        <BFormGroup class="flex-grow-1 mb-0" :label="`${t('events.field_event_name')}:`" label-for="event-form-venue">
          <input
            id="event-form-venue"
            v-model="form.venue"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('venue') }"
            :placeholder="t('events.field_event_name_helper')"
            data-testid="event-form-venue"
          >
          <div v-if="fieldError('venue')" class="invalid-feedback d-block" data-testid="event-form-venue-error">
            {{ fieldError('venue') }}
          </div>
        </BFormGroup>

        <BFormCheckbox id="event-form-online" v-model="form.online" class="mb-2" data-testid="event-form-online">
          {{ t('events.online_event_question') }}
        </BFormCheckbox>
      </div>

      <BFormGroup class="event-form-link" :label="`${t('events.field_event_link')}:`" label-for="event-form-link">
        <input
          id="event-form-link"
          v-model="form.link"
          type="url"
          class="form-control"
          :class="{ 'is-invalid': fieldError('link') }"
          :placeholder="t('events.field_event_link_helper')"
          data-testid="event-form-link"
        >
        <div v-if="fieldError('link')" class="invalid-feedback d-block" data-testid="event-form-link-error">
          {{ fieldError('link') }}
        </div>
      </BFormGroup>

      <div v-if="creating" class="event-form-group">
        <BFormGroup :label="`${t('events.field_event_group')}:`" label-for="event-form-group">
          <select
            id="event-form-group"
            v-model.number="form.idgroups"
            class="form-select"
            :class="{ 'is-invalid': fieldError('idgroups') }"
            data-testid="event-form-group"
          >
            <option :value="null" />
            <option v-for="g in groupOptionsSorted" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
          <div v-if="fieldError('idgroups')" class="invalid-feedback d-block" data-testid="event-form-group-error">
            {{ fieldError('idgroups') }}
          </div>
        </BFormGroup>
      </div>
      <div v-else class="event-form-group mb-3">
        <strong>{{ t('events.field_event_group') }}:</strong> {{ eventGroupName }}
      </div>

      <BFormGroup class="event-form-description" :label="`${t('events.field_event_desc')}:`" label-for="event-form-description">
        <RichTextEditor
          v-model="form.description"
          testid="event-form-description"
          :has-error="!!fieldError('description')"
        />
        <div v-if="fieldError('description')" class="invalid-feedback d-block" data-testid="event-form-description-error">
          {{ fieldError('description') }}
        </div>
        <small v-else class="form-text text-muted">{{ t('events.endof10_helper') }}</small>
      </BFormGroup>

      <BFormGroup class="event-form-date" :label="`${t('events.field_event_date')}:`" label-for="event-form-date">
        <!-- ssr:false (nuxt.config.ts) - no hydration mismatch risk, so this
             renders unwrapped (no <ClientOnly>) unlike a typical Nuxt app.
             vue-datepicker-next renders its own markup with inheritAttrs
             effectively off (data-testid/class attrs placed here never reach
             its DOM - verified by inspecting its render output) - the native
             fallback input below carries the test hook and the error text
             beneath is still shown either way. -->
        <DatePicker
          v-model:value="form.eventDate"
          type="date"
          value-type="format"
          format="YYYY-MM-DD"
          input-class="form-control d-none d-lg-block"
          :placeholder="t('events.no_date_selected')"
        />
        <input
          v-model="form.eventDate"
          type="date"
          class="form-control d-block d-lg-none"
          data-testid="event-form-date-native"
        >
        <div v-if="fieldError('eventDate')" class="invalid-feedback d-block" data-testid="event-form-date-error">
          {{ fieldError('eventDate') }}
        </div>
      </BFormGroup>

      <div class="event-form-time d-flex gap-2">
        <BFormGroup :label="`${t('events.field_event_time')}:`" label-for="event-form-start" class="flex-grow-1">
          <input
            id="event-form-start"
            v-model="form.startTime"
            type="time"
            class="form-control"
            :class="{ 'is-invalid': fieldError('startTime') }"
            data-testid="event-form-start"
            @change="onStartTimeChange"
          >
          <div v-if="fieldError('startTime')" class="invalid-feedback d-block" data-testid="event-form-start-error">
            {{ fieldError('startTime') }}
          </div>
        </BFormGroup>
        <BFormGroup label-for="event-form-end" class="flex-grow-1 align-self-end">
          <input
            id="event-form-end"
            v-model="form.endTime"
            type="time"
            class="form-control"
            :class="{ 'is-invalid': fieldError('endTime') }"
            data-testid="event-form-end"
          >
          <div v-if="fieldError('endTime')" class="invalid-feedback d-block" data-testid="event-form-end-error">
            {{ fieldError('endTime') }}
          </div>
        </BFormGroup>
      </div>

      <div class="event-form-address">
        <LocationPicker
          v-model:location="form.location"
          :show-postcode="false"
          :label="t('events.field_event_venue')"
          :placeholder="t('events.field_venue_placeholder')"
          :help-text="t('events.field_venue_helper')"
          :error-text="t('events.address_error')"
          :has-error="!!fieldError('location')"
        />
        <div v-if="groupLocationText && !form.online" class="mb-3">
          <BButton variant="primary" size="sm" data-testid="event-form-use-group-location" @click="useGroupLocation">
            {{ t('events.field_venue_use_group') }}
          </BButton>
        </div>
      </div>

      <BCard v-if="showAdminOnly" no-body class="event-form-admin" data-testid="event-form-admin">
        <BCardHeader class="d-flex align-items-center gap-2">
          <svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" aria-hidden="true">
            <g fill="#0394a6">
              <path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z" />
              <ellipse cx="6.472" cy=".217" rx=".274" ry=".217" />
              <ellipse cx="8.528" cy=".217" rx=".274" ry=".217" />
              <path d="M6.472 0h2.056v1.394H6.472z" />
              <path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z" />
              <ellipse cx="8.528" cy="14.783" rx=".274" ry=".217" />
              <ellipse cx="6.472" cy="14.783" rx=".274" ry=".217" />
              <path d="M6.472 13.606h2.056V15H6.472z" />
              <path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z" />
              <path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z" />
              <path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z" />
              <path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z" />
              <path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z" />
              <path d="M0 6.472v2.056h1.394V6.472H0z" />
              <path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z" />
              <path d="M15 8.528V6.472h-1.394v2.056H15z" />
              <path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z" />
              <path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z" />
              <path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z" />
              <path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z" />
              <path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z" />
            </g>
          </svg>
          {{ t('groups.group_admin_only') }}
        </BCardHeader>
        <BCardBody>
          <GroupNetworkData v-model="networkData" />
        </BCardBody>
      </BCard>

      <div v-if="!creating && canApprove && !approved" class="event-form-approve mb-3">
        <BFormGroup :label="`${t('events.approve_event')}:`" label-for="event-form-approve">
          <select id="event-form-approve" v-model="form.moderate" class="form-select" data-testid="event-approve">
            <option value="" />
            <option value="approve">{{ t('client.groups.approve_option') }}</option>
          </select>
          <small v-if="form.moderate === 'approve'" class="form-text text-muted">
            {{ t('client.events.moderate_approve_help') }}
          </small>
        </BFormGroup>
      </div>

      <!-- EventAddEdit.vue:94 -->
      <BAlert v-if="justCreated" :model-value="true" variant="success" class="mt-2 mb-2" data-testid="event-form-created">
        {{ autoApprove ? t('events.created_success_message_autoapproved') : t('events.created_success_message') }}
      </BAlert>

      <div class="event-form-buttons d-flex align-items-center justify-content-between mt-3">
        <!-- EventAddEdit.vue:97-125. Two distinct branches, and the edit one
             was missing here entirely - this used to render the notice only
             while creating, so an editing host saw nothing where develop
             shows them what saving will do. On edit develop also suppresses
             it once the event is already approved-or-being-approved
             (`!canApprove && moderate !== 'approve'`), and right after a
             create (`!justCreated`), where the green alert above says it
             better. -->
        <div v-if="creating" class="flex-grow-1 text-end pe-3" data-testid="event-form-notice">
          {{ autoApprove ? t('events.before_submit_text_autoapproved') : t('events.before_submit_text') }}
        </div>
        <div v-else-if="!justCreated" class="flex-grow-1 text-end pe-3">
          <span v-if="autoApprove" data-testid="event-form-notice">
            {{ t('events.before_submit_text_autoapproved') }}
          </span>
          <span v-else-if="!canApprove && form.moderate !== 'approve'" data-testid="event-form-notice">
            {{ t('events.before_submit_text') }}
          </span>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- EventAddEdit.vue:127 - develop offers Duplicate from the edit
               form as well as from the view page's actions dropdown. -->
          <BButton
            v-if="!creating"
            variant="primary"
            :to="`/party/duplicate/${eventId}`"
            data-testid="event-form-duplicate"
          >
            {{ t('events.duplicate_event') }}
          </BButton>
          <BButton type="submit" variant="primary" :disabled="submitting" data-testid="event-form-submit">
            {{ creating ? t('events.create_event') : t('events.save_event') }}
          </BButton>
        </div>
      </div>
    </div>
  </BForm>
</template>

<style scoped lang="scss">
// Ports EventAddEditPage.vue's b-card.box (white bg, hard offset
// drop-shadow, 1px solid black border, square corners) - same values as
// GroupForm.vue's page-level .group-create-box, but placed on the form
// itself (rather than at each consuming page) since EventForm.vue, unlike
// GroupForm.vue, is used by three pages (create/edit/duplicate) and only
// create/edit/duplicate under events/ are in scope here.
.event-form-box {
  background-color: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px #222;
  border-radius: 0;
  padding: 1.5rem;
}

// Grid layout matching EventAddEdit.vue's .eae-layout (findings/
// parity-v2/events.md #1): single column on mobile, following DOM order
// (already the develop row order - venue+online/link/group/description,
// then date/time/address, then admin/approve/buttons); two columns from
// the lg breakpoint (992px) up, same technique as GroupForm.vue's
// .group-form-layout - only the right-hand fields are repositioned into
// column 2, the left-hand ones keep their mobile row numbers unchanged.
.event-form-layout {
  display: grid;
  grid-template-columns: 1fr;
}

.event-form-venue {
  grid-row: 1 / 2;
  grid-column: 1 / 2;
}

.event-form-link {
  grid-row: 2 / 3;
  grid-column: 1 / 2;
}

.event-form-group {
  grid-row: 3 / 4;
  grid-column: 1 / 2;
}

.event-form-description {
  grid-row: 4 / 5;
  grid-column: 1 / 2;
}

.event-form-date {
  grid-row: 5 / 6;
  grid-column: 1 / 2;

  // Rendered-parity fix: vue-datepicker-next's own default (index.css)
  // puts the calendar icon on the right (`.mx-icon-calendar { right: 8px }`)
  // and pads the input to match; develop's b-form-datepicker puts it on
  // the left instead - swapped here to match, rather than the icon itself
  // (a plain CSS-drawn glyph, not an image asset).
  :deep(.mx-icon-calendar) {
    right: auto;
    left: 8px;
  }

  :deep(.mx-input) {
    padding-left: 30px;
    padding-right: 10px;
  }
}

.event-form-time {
  grid-row: 6 / 7;
  grid-column: 1 / 2;
}

.event-form-address {
  grid-row: 7 / 8;
  grid-column: 1 / 2;
}

.event-form-admin {
  grid-row: 8 / 9;
  grid-column: 1 / 2;
}

.event-form-approve {
  grid-row: 9 / 10;
  grid-column: 1 / 2;
}

.event-form-buttons {
  grid-row: 10 / 11;
  grid-column: 1 / 2;
}

@media (min-width: 992px) {
  .event-form-layout {
    grid-template-columns: 1fr 1fr;
    column-gap: 40px;
  }

  .event-form-date {
    grid-row: 1 / 2;
    grid-column: 2 / 3;
  }

  .event-form-time {
    grid-row: 2 / 3;
    grid-column: 2 / 3;
  }

  .event-form-address {
    grid-row: 3 / 4;
    grid-column: 2 / 3;
  }

  // Below whichever column is taller (column 1 tops out at row 4/5's
  // description, column 2 at row 3/4's address) - spans both columns, same
  // as GroupForm.vue's .group-form-admin/.group-form-buttons.
  .event-form-admin {
    grid-row: 5 / 6;
    grid-column: 1 / 3;
  }

  .event-form-approve {
    grid-row: 6 / 7;
    grid-column: 1 / 3;
  }

  .event-form-buttons {
    grid-row: 7 / 8;
    grid-column: 1 / 3;
  }
}
</style>
