<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// DELETE /api/v2/users/me. Functional spec:
// resources/js/components/DeleteAccountTab.vue +
// resources/views/user/profile/account.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile.
const { t } = useI18n()
const profileStore = useProfileStore()

const showModal = ref(false)
const deleting = ref(false)
const feedback = ref('')

async function deleteAccount() {
  deleting.value = true
  feedback.value = ''

  try {
    await profileStore.deleteAccount()
    await navigateTo('/login')
  } catch (err) {
    feedback.value = err?.data?.message || t('general.error_occurred')
    deleting.value = false
    showModal.value = false
  }
}
</script>

<template>
  <div data-testid="delete-account-tab">
    <BAlert v-if="feedback" :model-value="true" variant="danger" dismissible data-testid="delete-account-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span>{{ t('auth.delete_account_text') }}</span>
      <BButton variant="danger" :disabled="deleting" data-testid="delete-account-button" @click="showModal = true">
        {{ t('auth.delete_account') }}
      </BButton>
    </div>

    <BModal :model-value="showModal" :title="t('partials.are_you_sure')" hide-footer data-testid="delete-account-modal" @hide="showModal = false">
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
