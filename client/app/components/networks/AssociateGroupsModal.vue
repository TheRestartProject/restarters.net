<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNetworksStore } from '../../stores/networks.js'

// "Add groups" modal for /networks/{id} (design.md §6.2 Phase E task E1).
// Functional spec: resources/views/networks/partials/add-group-modal.blade.php
// + NetworkController@associateGroup - a multi-select of groups not
// currently in the network, submitted as one batch.
//
// POST /api/v2/networks/{id}/groups (NetworkController::associateGroupsv2),
// Administrator or coordinator-of-network only.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  networkId: {
    type: Number,
    required: true,
  },
  networkName: {
    type: String,
    default: '',
  },
  // Groups not currently in the network: [{id, name}].
  candidates: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const networksStore = useNetworksStore()

const selectedIds = ref([])
const submitting = ref(false)
const error = ref('')
const successMessage = ref('')

function reset() {
  selectedIds.value = []
  error.value = ''
  successMessage.value = ''
}

async function submit() {
  error.value = ''
  successMessage.value = ''

  if (!selectedIds.value.length) {
    error.value = t('networks.show.add_groups_warning_none_selected')
    return
  }

  submitting.value = true

  try {
    await networksStore.associateGroups(props.networkId, selectedIds.value)
    successMessage.value = t('networks.show.add_groups_success', { number: selectedIds.value.length }, selectedIds.value.length)
    selectedIds.value = []
  } catch (err) {
    error.value = err?.data?.message || t('client.networks.associate_groups_error')
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
  <BModal
    :model-value="show"
    data-testid="network-associate-groups-modal"
    :title="t('networks.show.add_groups_modal_header', { name: networkName })"
    no-footer
    @hide="close"
  >
    <BAlert v-if="successMessage" :model-value="true" variant="success" data-testid="network-associate-groups-success">
      {{ successMessage }}
    </BAlert>
    <BAlert v-if="error" :model-value="true" variant="danger" data-testid="network-associate-groups-error">
      {{ error }}
    </BAlert>

    <BForm data-testid="network-associate-groups-form" @submit.prevent="submit">
      <BFormGroup :label="`${t('networks.show.add_groups_select_label')}:`" label-for="network-associate-groups-select">
        <select
          id="network-associate-groups-select"
          v-model="selectedIds"
          multiple
          size="8"
          class="form-control"
          data-testid="network-associate-groups-select"
        >
          <option v-for="group in candidates" :key="group.id" :value="group.id" :data-testid="`network-associate-groups-option-${group.id}`">
            {{ group.name }}
          </option>
        </select>
        <small v-if="!candidates.length" class="text-muted d-block">{{ t('networks.show.none') }}</small>
      </BFormGroup>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <BButton variant="outline-secondary" data-testid="network-associate-groups-cancel" @click="close">
          {{ t('partials.cancel') }}
        </BButton>
        <BButton type="submit" variant="primary" :disabled="submitting" data-testid="network-associate-groups-submit">
          {{ t('networks.show.add_groups_save_button') }}
        </BButton>
      </div>
    </BForm>
  </BModal>
</template>
