<script setup>
// One create/edit form control, factored out of AdminCrudTable.vue so its
// create form and edit form (both single-column, matching the live
// AdminCrudPage.vue baseline this branch already migrated to pre-Phase-F -
// see AdminCrudTable.vue's own doc comment) render the same field types
// without duplicating the text/select/textarea/number markup twice.
defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  // Full `${testidPrefix}-create-${field.key}` / `${testidPrefix}-edit-${field.key}` -
  // doubles as both the DOM id (for the label's for=) and data-testid, same
  // as the markup this replaces.
  testid: {
    type: String,
    required: true,
  },
})
const emit = defineEmits(['update:modelValue'])

// Mirrors Vue's own v-model.number modifier (looseToNumber): parseFloat the
// raw string, falling back to it unparsed rather than coercing to NaN - the
// exact behaviour the native `v-model.number` input this replaces had.
function onNumberInput(event) {
  const raw = event.target.value
  const parsed = parseFloat(raw)
  emit('update:modelValue', Number.isNaN(parsed) ? raw : parsed)
}
</script>

<template>
  <BFormGroup :label="`${field.label}:`" :label-for="testid">
    <BFormSelect
      v-if="field.type === 'select'"
      :id="testid"
      :model-value="modelValue"
      :data-testid="testid"
      @update:model-value="emit('update:modelValue', $event)"
    >
      <option v-for="opt in field.options" :key="String(opt.value)" :value="opt.value">{{ opt.text }}</option>
    </BFormSelect>
    <textarea
      v-else-if="field.type === 'textarea'"
      :id="testid"
      :value="modelValue"
      class="form-control"
      :class="{ 'is-invalid': error }"
      :rows="field.rows || 3"
      :maxlength="field.maxLength"
      :data-testid="testid"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <input
      v-else-if="field.type === 'number'"
      :id="testid"
      type="number"
      :value="modelValue"
      class="form-control"
      :class="{ 'is-invalid': error }"
      :data-testid="testid"
      @input="onNumberInput"
    >
    <input
      v-else
      :id="testid"
      type="text"
      :value="modelValue"
      class="form-control"
      :class="{ 'is-invalid': error }"
      :maxlength="field.maxLength"
      :data-testid="testid"
      @input="emit('update:modelValue', $event.target.value)"
    >
    <div v-if="error" class="invalid-feedback d-block" :data-testid="`${testid}-error`">{{ error }}</div>
  </BFormGroup>
</template>
