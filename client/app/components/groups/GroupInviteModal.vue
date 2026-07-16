<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'

// POST /api/v2/groups/{id}/invites (api-contracts-phase-b.md B2, not yet
// implemented server-side): {emails:[...], message?} ->
// {invites_sent, invalid:[...]}. Functional spec:
// resources/views/includes/modals/group-invite-to.blade.php's email-invite
// form. The Blade modal also has a "shareable link" tab
// (group.shareable_link) - the v2 Group resource has no equivalent field
// (see docs/nuxt-migration/api-gaps.md B5), so that half is dropped here
// rather than built against data that doesn't exist.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  groupId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const groupsStore = useGroupsStore()

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
    fieldErrors.value = { emails: [t('client.groups.invite_emails_required')] }
    return
  }

  const malformed = emails.filter((e) => !EMAIL_RE.test(e))
  if (malformed.length) {
    fieldErrors.value = { emails: [t('client.groups.invite_emails_invalid', { emails: malformed.join(', ') })] }
    return
  }

  submitting.value = true

  try {
    const data = await groupsStore.inviteVolunteers(props.groupId, {
      emails,
      message: message.value || undefined,
    })

    if (data?.invalid?.length) {
      successMessage.value = t('groups.invite_success_apart_from', { emails: data.invalid.join(', ') })
    } else {
      successMessage.value = t('groups.invite_success')
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
  <BModal :model-value="show" data-testid="group-invite-modal" :title="t('groups.invite_group_header_link')" no-footer @hide="close">
    <BAlert v-if="successMessage" :model-value="true" variant="success" data-testid="group-invite-success">
      {{ successMessage }}
    </BAlert>
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="group-invite-error">
      {{ generalError }}
    </BAlert>

    <BForm data-testid="group-invite-form" @submit.prevent="submit">
      <BFormGroup :label="`${t('groups.email_addresses_field')}:`" label-for="group-invite-emails">
        <textarea
          id="group-invite-emails"
          v-model="emailsText"
          class="form-control"
          rows="3"
          data-testid="group-invite-emails"
        />
        <small class="text-muted d-block">{{ t('groups.type_email_addresses_message') }}</small>
        <div v-if="fieldErrors.emails" class="invalid-feedback d-block" data-testid="group-invite-emails-error">
          {{ fieldErrors.emails[0] }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('groups.message_header')}:`" label-for="group-invite-message" class="mt-3">
        <textarea
          id="group-invite-message"
          v-model="message"
          class="form-control"
          rows="3"
          :placeholder="t('groups.message_example_text')"
          data-testid="group-invite-message"
        />
      </BFormGroup>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="#" data-testid="group-invite-cancel" @click.prevent="close">
          {{ t('groups.cancel_invites_link') }}
        </a>
        <BButton type="submit" variant="primary" :disabled="submitting" data-testid="group-invite-submit">
          {{ t('groups.send_invite_button') }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>
