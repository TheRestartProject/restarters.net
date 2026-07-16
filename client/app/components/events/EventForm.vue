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
//  - No NetworkData.vue custom-field editor (the admin-only "event-admin"
//    card). Because updateEventv2 unconditionally overwrites `network_data`
//    with whatever's submitted (validateEventParams reads it unconditionally,
//    same as updateGroupv2), this component round-trips the event's
//    existing network_data unchanged on every save rather than dropping it
//    silently.
//  - The event-group multiselect becomes a plain <select> (vue-multiselect
//    isn't installed for this migration - design.md §2 only lists it for
//    the group/tag pickers already ported in B6).
//  - `duplicateFrom`'s image-upload semantics don't apply (legacy's create
//    form is also the duplicate form and has no image tab either - the
//    dropzone only ever lived in edit.blade.php, so TusImageUpload for
//    events lives in pages/party/edit/[id].vue only, matching legacy 1:1).
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
})

const emit = defineEmits(['created', 'updated'])

const { t } = useI18n()
const eventsStore = useEventsStore()
const groupsStore = useGroupsStore()

const creating = computed(() => !props.eventId)
const canApprove = computed(() => !creating.value && props.isAdmin)

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

// Round-tripped, never edited here - see the NetworkData scope-cut note
// above.
const networkData = props.initialEvent?.network_data || {}
const approved = ref(!!props.initialEvent?.approved)
const eventGroupName = computed(() => props.initialEvent?.group?.name || '')

const timezones = ref([])
const timezoneValid = computed(() => !form.timezone || !timezones.value.length || timezones.value.includes(form.timezone))
const timezoneTouched = ref(false)

const groupOptionsSorted = computed(() => [...props.groups].sort((a, b) => a.name.localeCompare(b.name)))

const selectedGroupDetail = computed(() => groupsStore.details[form.idgroups] || null)
const groupLocationText = computed(() => selectedGroupDetail.value?.location?.location || null)

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

onMounted(async () => {
  const { $api } = useNuxtApp()

  if ($api?.config) {
    try {
      const zones = await $api.config.timezones()
      timezones.value = (zones || []).map((z) => z.name)
    } catch {
      // Non-critical: the field still works as free text without the
      // client-side validity check.
    }
  }

  // "If only one group, default to that" (EventAddEdit.vue's created()).
  if (creating.value && !form.idgroups && groupOptionsSorted.value.length === 1) {
    form.idgroups = groupOptionsSorted.value[0].id
  }
})

// Fetches the selected group's full record (for its timezone default and
// the "use group location" shortcut - .event-address's useGroup() in
// VenueAddress.vue) whenever the selection changes, including the initial
// mount. Only *applies* the group's timezone as a default when: this is a
// live user-driven change (oldId !== undefined - false on the immediate
// initial call), or the timezone field is still empty (a fresh create with
// no prior value) - never silently overwriting a value that arrived via
// edit/duplicate prefill or that the user already typed themselves.
watch(
  () => form.idgroups,
  async (id, oldId) => {
    if (!id) return

    const detail = await groupsStore.fetchDetails(id)
    const isUserChange = oldId !== undefined

    if (detail?.timezone && !timezoneTouched.value && (isUserChange || !form.timezone)) {
      form.timezone = detail.timezone
    }
  },
  { immediate: true }
)

function onTimezoneInput() {
  timezoneTouched.value = true
}

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
  if (!timezoneValid.value) {
    errors.timezone = [t('partials.validate_timezone')]
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
  }

  if (creating.value) {
    payload.groupid = form.idgroups
  } else {
    payload.network_data = JSON.stringify(networkData)

    if (canApprove.value && !approved.value && form.moderate === 'approve') {
      payload.moderate = 'approve'
    }
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
  <BForm data-testid="event-form" @submit.prevent="submit">
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="event-form-error">
      <!-- eslint-disable-next-line vue/no-v-html -->
      <span v-html="generalError" />
    </BAlert>

    <BFormGroup :label="`${t('events.field_event_name')}:`" label-for="event-form-venue">
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

    <BFormGroup label-for="event-form-online">
      <BFormCheckbox id="event-form-online" v-model="form.online" data-testid="event-form-online">
        {{ t('events.online_event_question') }}
      </BFormCheckbox>
    </BFormGroup>

    <BFormGroup :label="`${t('events.field_event_link')}:`" label-for="event-form-link">
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

    <div v-if="creating">
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
    <div v-else class="mb-3">
      <strong>{{ t('events.field_event_group') }}:</strong> {{ eventGroupName }}
    </div>

    <BFormGroup :label="`${t('events.field_event_desc')}:`" label-for="event-form-description">
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

    <BFormGroup :label="`${t('events.field_event_date')}:`" label-for="event-form-date">
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

    <div class="d-flex gap-2">
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

    <BFormGroup :label="`${t('groups.timezone')}:`" label-for="event-form-timezone">
      <input
        id="event-form-timezone"
        v-model="form.timezone"
        type="text"
        list="event-form-timezones"
        class="form-control"
        :class="{ 'is-invalid': !timezoneValid }"
        data-testid="event-form-timezone"
        @input="onTimezoneInput"
      >
      <datalist id="event-form-timezones">
        <option v-for="zone in timezones" :key="zone" :value="zone" />
      </datalist>
      <small class="form-text text-muted">{{ t('groups.timezone_placeholder') }}</small>
    </BFormGroup>

    <div v-if="!creating && canApprove && !approved" class="event-approve mb-3">
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

    <div class="d-flex justify-content-end mt-3">
      <BButton type="submit" variant="primary" :disabled="submitting" data-testid="event-form-submit">
        {{ creating ? t('events.create_event') : t('events.save_event') }}
      </BButton>
    </div>
  </BForm>
</template>
