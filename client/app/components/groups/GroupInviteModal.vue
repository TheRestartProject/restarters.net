<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'
import IconChainLink from '../icons/IconChainLink.vue'

// POST /api/v2/groups/{id}/invites (api-contracts-phase-b.md B2, not yet
// implemented server-side): {emails:[...], message?} ->
// {invites_sent, invalid:[...]}. Functional spec:
// resources/views/includes/modals/group-invite-to.blade.php's email-invite
// form. That modal also has a "shareable link" panel, reached by the
// chain-link toggle in its header, which swaps the email form for a box
// holding group.shareable_link - a URL anyone can follow to join directly.
// The link is not a secret: claiming it grants the same status 1 / role
// RESTARTER that the public join button does, which is why the Group
// resource exposes it unconditionally.
// Which half of the modal is showing. Blade toggles between the two with
// a Bootstrap 4 collapse; the panels are alternatives, never both at once.
const showingLink = ref(false)

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  groupId: {
    type: Number,
    required: true,
  },
  // Group.shareable_link. Passed in rather than fetched: the page already
  // holds the group, and the modal is mounted alongside it.
  shareableLink: {
    type: String,
    default: '',
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

    <div class="d-flex justify-content-end mb-2">
      <a
        href="#"
        class="invite-modal__toggle text-dark d-inline-flex align-items-center"
        data-testid="group-invite-toggle"
        @click.prevent="showingLink = !showingLink"
      >
        <IconChainLink class="me-1" />
        {{ showingLink ? t('groups.email_invite') : t('groups.shareable_link') }}
      </a>
    </div>

    <div v-if="showingLink" data-testid="group-invite-link-panel">
      <BFormGroup :label="`${t('groups.shareable_link_box')}:`" label-for="group-invite-link">
        <input
          id="group-invite-link"
          :value="shareableLink"
          type="text"
          class="form-control"
          autocomplete="off"
          data-testid="group-invite-link"
          @focus="$event.target.select()"
        >
      </BFormGroup>
      <small class="text-muted d-block">{{ t('groups.type_shareable_link_message') }}</small>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="#" data-testid="group-invite-link-cancel" @click.prevent="close">
          {{ t('groups.cancel_invites_link') }}
        </a>
        <BButton variant="primary" data-testid="group-invite-link-done" @click="close">
          {{ t('groups.done_button') }}
        </BButton>
      </div>
    </div>

    <BForm v-else data-testid="group-invite-form" @submit.prevent="submit">
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
