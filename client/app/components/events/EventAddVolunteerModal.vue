<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'
import { useEventsStore } from '../../stores/events.js'

// Ports EventAddVolunteerModal.vue (gap 14, api-gaps.md - unblocked now
// PUT /api/events/{id}/volunteers is wired up, see api/EventAPI.js's
// addVolunteer doc comment for why it's a v1 path). Two ways to add a
// volunteer to a finished/in-progress event: pick an existing member of
// the event's hosting group (GET /api/v2/groups/{id}/volunteers, same
// endpoint GroupVolunteers.vue already uses), or "not registered" with a
// manually-entered name/email - matching legacy's own two-branch form
// exactly, including its validation ("name and email are optional, but not
// both" when adding someone not-registered).
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  eventId: {
    type: Number,
    required: true,
  },
  groupId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const groupsStore = useGroupsStore()
const eventsStore = useEventsStore()

const user = ref(null)
const fullName = ref('')
const volunteerEmailAddress = ref('')
const submitting = ref(false)
const generalError = ref('')

const groupOptions = computed(() =>
  (groupsStore.volunteers.data || []).map((v) => ({ value: v.user, text: v.name }))
)

const disabled = computed(() => {
  if (user.value === null) return true
  if (user.value === 'not-registered' && !volunteerEmailAddress.value && !fullName.value) return true
  return false
})

// A plain v-model on the <select> would need `.number`, but `user` isn't
// always numeric (the 'not-registered' sentinel) - handled explicitly
// instead. Plain string<->string comparison on the DOM value is enough to
// pick the right <option> back out (native <select> values are always
// strings anyway), so this doesn't need Vue's object-identity v-model
// machinery.
function onUserChange(raw) {
  if (raw === '') {
    user.value = null
  } else if (raw === 'not-registered') {
    user.value = 'not-registered'
  } else {
    user.value = Number(raw)
  }
}

watch(
  () => props.show,
  (shown) => {
    if (shown) {
      if (props.groupId) {
        groupsStore.fetchVolunteers(props.groupId).catch(() => {})
      }
    } else {
      user.value = null
      fullName.value = ''
      volunteerEmailAddress.value = ''
      generalError.value = ''
    }
  },
)

async function submit() {
  generalError.value = ''
  submitting.value = true

  try {
    await eventsStore.addVolunteer(props.eventId, {
      user: user.value,
      full_name: fullName.value || null,
      volunteer_email_address: volunteerEmailAddress.value || null,
    })
    close()
  } catch {
    generalError.value = t('partials.something_wrong')
  } finally {
    submitting.value = false
  }
}

function close() {
  emit('close')
}
</script>

<template>
  <BModal
    :model-value="show"
    data-testid="event-add-volunteer-modal"
    :title="t('events.add_volunteer_modal_heading')"
    @hide="close"
  >
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="event-add-volunteer-error">
      {{ generalError }}
    </BAlert>

    <BFormGroup :label="`${t('events.group_member')}:`" label-for="event-add-volunteer-user">
      <select
        id="event-add-volunteer-user"
        class="form-select"
        data-testid="event-add-volunteer-user"
        :value="user"
        @change="onUserChange($event.target.value)"
      >
        <option value="">{{ t('events.option_default') }}</option>
        <option v-for="opt in groupOptions" :key="opt.value" :value="opt.value">{{ opt.text }}</option>
        <option value="not-registered">{{ t('events.option_not_registered') }}</option>
      </select>
    </BFormGroup>

    <template v-if="user === 'not-registered'">
      <BFormGroup :label="`${t('events.full_name')}:`" label-for="event-add-volunteer-name" class="mt-3">
        <input
          id="event-add-volunteer-name"
          v-model="fullName"
          type="text"
          class="form-control"
          :placeholder="t('events.full_name_helper')"
          data-testid="event-add-volunteer-name"
        >
      </BFormGroup>

      <BFormGroup :label="`${t('events.volunteer_email_address')}:`" label-for="event-add-volunteer-email" class="mt-3">
        <input
          id="event-add-volunteer-email"
          v-model="volunteerEmailAddress"
          type="email"
          class="form-control"
          data-testid="event-add-volunteer-email"
        >
      </BFormGroup>
      <small class="form-text text-muted">{{ t('events.message_volunteer_email_address') }}</small>
    </template>

    <template #footer>
      <BButton
        variant="primary"
        :disabled="disabled || submitting"
        data-testid="event-add-volunteer-submit"
        @click="submit"
      >
        {{ t('events.volunteer_attended_button') }}
      </BButton>
    </template>
  </BModal>
</template>
