<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '../../stores/events.js'

// POST /api/v2/events/{id}/invites (api-contracts-phase-c.md C1d):
// {emails:[...], message?} -> {invites_sent, invalid:[...]}. Replaces
// EventInviteModal.vue's hidden-form POST to /party/invite. Modelled
// directly on components/groups/GroupInviteModal.vue (same shape); the
// legacy component's "select group members" multiselect (backed by
// GET /api/v2/groups/{id}/volunteers?exclude_event={id}, which already
// exists per the contract doc) is dropped here - manual email entry covers
// the same endpoint and keeps this component in step with the group one,
// see docs/nuxt-migration/api-gaps.md Phase C for the scoping note.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  eventId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const eventsStore = useEventsStore()

const emailsText = ref('')
const message = ref('')
const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})
const successMessage = ref('')

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function parseEmails(text) {
  return text
    .split(/[,;\n]+/)
    .map((s) => s.trim())
    .filter(Boolean)
}

function reset() {
  generalError.value = ''
  fieldErrors.value = {}
  successMessage.value = ''
}

async function submit() {
  reset()

  const emails = parseEmails(emailsText.value)

  if (!emails.length) {
    fieldErrors.value = { emails: [t('client.events.invite_emails_required')] }
    return
  }

  const malformed = emails.filter((e) => !EMAIL_RE.test(e))
  if (malformed.length) {
    fieldErrors.value = { emails: [t('client.events.invite_emails_invalid', { emails: malformed.join(', ') })] }
    return
  }

  submitting.value = true

  try {
    const data = await eventsStore.inviteVolunteers(props.eventId, {
      emails,
      message: message.value || undefined,
    })

    if (data?.invalid?.length) {
      successMessage.value = t('client.events.invite_success_apart_from', { emails: data.invalid.join(', ') })
    } else {
      successMessage.value = t('events.invite_success')
    }

    emailsText.value = ''
    message.value = ''
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
    }
    generalError.value = err?.message || t('partials.something_wrong')
  } finally {
    submitting.value = false
  }
}

function close() {
  reset()
  emit('close')
}
</script>

<template>
  <BModal :model-value="show" data-testid="event-invite-modal" :title="t('events.invite_restarters_modal_heading')" no-footer @hide="close">
    <BAlert v-if="successMessage" :model-value="true" variant="success" data-testid="event-invite-success">
      {{ successMessage }}
    </BAlert>
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="event-invite-error">
      {{ generalError }}
    </BAlert>

    <BForm data-testid="event-invite-form" @submit.prevent="submit">
      <BFormGroup :label="`${t('events.manual_invite_box')}:`" label-for="event-invite-emails">
        <textarea
          id="event-invite-emails"
          v-model="emailsText"
          class="form-control"
          rows="3"
          data-testid="event-invite-emails"
        />
        <small class="text-muted d-block">{{ t('events.type_email_addresses_message') }}</small>
        <div v-if="fieldErrors.emails" class="invalid-feedback d-block" data-testid="event-invite-emails-error">
          {{ fieldErrors.emails[0] }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('events.message_to_restarters')}:`" label-for="event-invite-message" class="mt-3">
        <textarea
          id="event-invite-message"
          v-model="message"
          class="form-control"
          rows="3"
          :placeholder="t('events.sample_text_message_to_restarters')"
          data-testid="event-invite-message"
        />
      </BFormGroup>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="#" data-testid="event-invite-cancel" @click.prevent="close">
          {{ t('events.cancel_invites_link') }}
        </a>
        <BButton type="submit" variant="primary" :disabled="submitting" data-testid="event-invite-submit">
          {{ t('events.send_invite_button') }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>
