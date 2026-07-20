<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Admin-only network-specific custom-field editor (findings/parity-v2/
// group-forms.md #5). Ports resources/js/components/NetworkData.vue +
// NetworkDataField.vue as a single component: renders each existing
// network_data key/value pair as an editable field, plus an "add new field"
// link that reveals a label input + "add field" button. GroupForm.vue
// round-trips the resulting object unchanged into the `network_data`
// payload key on save (updateGroupv2 overwrites it wholesale, unlike
// networks/tags which are only synced when present).
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const showAddNew = ref(false)
const newLabel = ref('')

function updateField(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function addField() {
  if (!newLabel.value) return

  emit('update:modelValue', { ...props.modelValue, [newLabel.value]: null })
  newLabel.value = ''
  showAddNew.value = false
}
</script>

<template>
  <div class="group-network-data" data-testid="group-network-data">
    <BFormGroup
      v-for="key in Object.keys(modelValue || {})"
      :key="`networkdata-${key}`"
      :label="`${key}:`"
      class="mt-1"
    >
      <input
        type="text"
        class="form-control"
        :value="modelValue[key]"
        :data-testid="`group-network-data-field-${key}`"
        @input="updateField(key, $event.target.value)"
      >
    </BFormGroup>

    <BButton v-if="!showAddNew" variant="link" class="p-0" data-testid="group-network-data-add-new" @click="showAddNew = true">
      {{ t('networks.edit.add_new_field') }}
    </BButton>
    <div v-if="showAddNew" class="mt-1">
      <label>{{ t('networks.edit.new_field_name') }}:</label>
      <input v-model="newLabel" type="text" class="form-control" data-testid="group-network-data-new-label">
      <BButton variant="primary" class="mt-2" data-testid="group-network-data-add-field" @click="addField">
        {{ t('networks.edit.add_field') }}
      </BButton>
    </div>
  </div>
</template>
