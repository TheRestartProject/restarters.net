<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useSessionStore } from '~/stores/session.js'
import { useNetworksStore } from '~/stores/networks.js'
import TusImageUpload from '~/components/forms/TusImageUpload.vue'

// /networks/[id]/edit - resources/views/networks/edit.blade.php, reached from
// Route::resource('networks', ...)->only(['index','show','edit','update']).
//
// This page was missing entirely, and the network-logo upload it owns had been
// bolted onto the VIEW page instead - so the view page carried a control
// develop doesn't have there, and develop's edit page had no counterpart at
// all. Moving function to a page that exists is not parity; the page is the
// parity.
//
// develop's form is exactly this small: a heading, a logo file input, a save
// button. Name/description/website are NOT editable here (NetworkController's
// update only handles the logo), so nothing else is invented.
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const { hasRole } = useAuth()
const sessionStore = useSessionStore()
const networksStore = useNetworksStore()

const id = computed(() => Number(route.params.id))
const network = computed(() => networksStore.current.data)

// Same gate the view page uses for its admin-only sections: an Administrator,
// or a coordinator of this particular network.
const isAdministrator = computed(() => hasRole('Administrator'))
const isCoordinatorHere = computed(() => (sessionStore.user?.networks || []).some((n) => n.id === id.value))
const canManage = computed(() => isAdministrator.value || isCoordinatorHere.value)

const logoError = ref('')
const logoSaved = ref(false)

useHead({ title: computed(() => (network.value ? network.value.name : '')) })

onMounted(() => {
  networksStore.fetchCurrent(id.value).catch(() => {})
})

async function onLogoUploaded({ uploadKey }) {
  logoError.value = ''
  logoSaved.value = false

  try {
    await networksStore.uploadLogo(id.value, uploadKey)
    logoSaved.value = true
  } catch {
    logoError.value = t('client.networks.logo_upload_error')
  }
}
</script>

<template>
  <div class="container py-4" data-testid="network-edit-page">
    <div v-if="networksStore.current.loading" data-testid="network-edit-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <BAlert v-else-if="!network" :model-value="true" variant="danger" data-testid="network-edit-error">
      {{ t('client.networks.load_error') }}
    </BAlert>

    <BAlert v-else-if="!canManage" :model-value="true" variant="danger" data-testid="network-edit-forbidden">
      {{ t('client.networks.edit_forbidden') }}
    </BAlert>

    <template v-else>
      <h1 data-testid="network-edit-heading">{{ t('client.networks.editing', { name: network.name }) }}</h1>

      <div class="mt-4">
        <label class="form-label" for="network-logo">{{ t('networks.edit.label_logo') }}:</label>
        <TusImageUpload
          id="network-logo"
          :current-image-url="network.logo || ''"
          data-testid="network-logo-upload"
          @uploaded="onLogoUploaded"
          @upload-error="logoError = $event"
        />
        <BAlert v-if="logoError" :model-value="true" variant="danger" class="mt-2" data-testid="network-edit-logo-error">
          {{ logoError }}
        </BAlert>
        <BAlert v-if="logoSaved" :model-value="true" variant="success" class="mt-2" data-testid="network-edit-logo-saved">
          {{ t('networks.edit.button_save') }}
        </BAlert>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <NuxtLink :to="`/networks/${id}`" class="btn btn-primary" data-testid="network-edit-done">
          {{ t('networks.edit.button_save') }}
        </NuxtLink>
      </div>
    </template>
  </div>
</template>
