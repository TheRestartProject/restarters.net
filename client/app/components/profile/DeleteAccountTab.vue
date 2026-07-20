<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// DELETE /api/v2/users/me (own profile) or DELETE /api/v2/users/{id}
// (gap 5 - an Administrator deleting someone else's account, via
// profileStore.deleteUser). Functional spec:
// resources/js/components/DeleteAccountTab.vue +
// resources/views/user/profile/account.blade.php. See stores/profile.js's
// class doc comment for why the two are kept as separate store actions.
const props = defineProps({
  targetId: {
    type: Number,
    default: null,
  },
  isOwnProfile: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()

const showModal = ref(false)
const deleting = ref(false)
const feedback = ref('')

async function deleteAccount() {
  deleting.value = true
  feedback.value = ''

  try {
    if (props.isOwnProfile) {
      await profileStore.deleteAccount()
      await navigateTo('/login')
    } else {
      // Deleting someone else's account doesn't touch the acting
      // Administrator's own session - back to the list they came from.
      await profileStore.deleteUser(props.targetId)
      await navigateTo('/user/all')
    }
  } catch (err) {
    feedback.value = err?.data?.message || t('general.error_occurred')
    deleting.value = false
    showModal.value = false
  }
}
</script>

<template>
  <div class="edit-panel" data-testid="delete-account-tab">
    <BAlert v-if="feedback" :model-value="true" variant="danger" dismissible data-testid="delete-account-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <div class="alert alert-danger">
      <div class="row">
        <div class="col-md-8 d-flex flex-column align-content-center">{{ t('auth.delete_account_text') }}</div>
        <div class="col-md-4 d-flex flex-column align-content-center">
          <BButton variant="danger" :disabled="deleting" data-testid="delete-account-button" @click="showModal = true">
            {{ t('auth.delete_account') }}
          </BButton>
        </div>
      </div>
    </div>

    <BModal :model-value="showModal" :title="t('partials.are_you_sure')" no-footer data-testid="delete-account-modal" @hide="showModal = false">
      <p>{{ t('auth.delete_account_text') }}</p>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="outline-secondary" @click="showModal = false">{{ t('partials.cancel') }}</BButton>
        <BButton variant="danger" :disabled="deleting" data-testid="delete-account-confirm" @click="deleteAccount">
          {{ t('auth.delete_account') }}
        </BButton>
      </div>
    </BModal>
  </div>
</template>
