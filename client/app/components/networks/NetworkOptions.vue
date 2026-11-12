<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNetworksStore } from '../../stores/networks.js'
import { FEATURE_KEYS } from '../../composables/useFeatureSwitches.js'

// "Options for groups in this network" (RES-2063): per feature, a network
// coordinator (or admin) chooses not set / always on / always off / on by
// default / off by default, each worded by what it means for the groups
// ("All groups collect item weights"). Saved with PATCH /api/v2/networks/{id}/options.
const props = defineProps({
  network: {
    type: Object,
    required: true,
  },
})

const { t } = useI18n()
const networksStore = useNetworksStore()

const CHOICES = ['', 'on', 'off', 'default_on', 'default_off']

function choiceLabel(key, value) {
  return value ? t(`client.features.${key}_network_${value}`) : t('client.features.network_not_set')
}

const values = reactive(Object.fromEntries(FEATURE_KEYS.map((k) => [k, props.network.features?.[k] || ''])))
const saving = ref(false)
const saved = ref(false)
const failed = ref(false)

async function save() {
  saving.value = true
  saved.value = false
  failed.value = false

  try {
    const features = Object.fromEntries(FEATURE_KEYS.map((k) => [k, values[k] || null]))
    await networksStore.updateOptions(props.network.id, { features })
    saved.value = true
  } catch {
    failed.value = true
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="mt-4" data-testid="network-options-form" @submit.prevent="save">
    <h2 class="h4" data-testid="network-options-title">{{ t('client.features.network_title') }}</h2>
    <p class="text-muted small">{{ t('client.features.network_intro') }}</p>

    <div v-for="key in FEATURE_KEYS" :key="key" class="mb-3">
      <label class="form-label fw-bold" :for="`network-option-${key}`">{{ t(`client.features.${key}`) }}</label>
      <select :id="`network-option-${key}`" v-model="values[key]" class="form-select" :data-testid="`network-option-${key}`">
        <option v-for="c in CHOICES" :key="c" :value="c">{{ choiceLabel(key, c) }}</option>
      </select>
      <small class="form-text text-muted">{{ t(`client.features.${key}_network_help`) }}</small>
    </div>

    <BAlert v-if="failed" :model-value="true" variant="danger" data-testid="network-options-error">
      {{ t('client.features.save_failed') }}
    </BAlert>
    <BAlert v-if="saved" :model-value="true" variant="success" data-testid="network-options-saved">
      {{ t('client.features.saved') }}
    </BAlert>

    <BButton type="submit" variant="primary" :disabled="saving" data-testid="network-options-save">
      {{ t('client.features.save') }}
    </BButton>
  </form>
</template>
