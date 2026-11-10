<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'
import { FEATURE_KEYS, MAX_CUSTOM_FIELDS } from '../../composables/useFeatureSwitches.js'

// The group's "Options" card (RES-2063): each feature On / Off / use the
// default, locked (and explained) when a network decides it, plus the custom
// item fields editor when custom fields are enabled. Has its own Save button:
// PATCH /api/v2/groups/{id}/options.
const props = defineProps({
  // The Group resource, including `features` and `custom_fields`.
  group: {
    type: Object,
    required: true,
  },
})

const { t } = useI18n()
const groupsStore = useGroupsStore()

const features = computed(() => props.group.features || {})

function settingToValue(setting) {
  if (setting === true) return 'on'
  if (setting === false) return 'off'
  return ''
}

// What each select shows: the group's own setting, or, when a network has
// locked it, what the network has decided.
const values = reactive(
  Object.fromEntries(
    FEATURE_KEYS.map((k) => {
      const f = features.value[k] || {}
      return [k, f.locked ? (f.enabled ? 'on' : 'off') : settingToValue(f.group_setting ?? null)]
    })
  )
)

function isLocked(key) {
  return !!features.value[key]?.locked
}

function stateLabel(on) {
  return t(on ? 'client.features.state_on' : 'client.features.state_off')
}

function defaultLabel(key) {
  return t('client.features.group_use_default', { state: stateLabel(!!features.value[key]?.inherited) })
}

// The editor follows the select as it is now, so turning custom fields on
// shows it straight away.
const customFieldsEnabled = computed(() => {
  if (isLocked('custom_fields')) return !!features.value.custom_fields?.enabled
  if (values.custom_fields === 'on') return true
  if (values.custom_fields === 'off') return false
  return !!features.value.custom_fields?.inherited
})

const rows = ref(
  (props.group.custom_fields || []).map((f) => ({
    key: f.key,
    label: f.label,
    type: f.type,
    optionsText: (f.options || []).join('\n'),
  }))
)

function addField() {
  if (rows.value.length >= MAX_CUSTOM_FIELDS) return
  rows.value.push({ key: null, label: '', type: 'text', optionsText: '' })
}

function removeField(index) {
  rows.value.splice(index, 1)
}

function fieldsPayload() {
  return rows.value.map((r) => {
    const field = { label: r.label.trim(), type: r.type }
    if (r.key) field.key = r.key
    if (r.type === 'choice') {
      field.options = r.optionsText.split('\n').map((o) => o.trim()).filter(Boolean)
    }
    return field
  })
}

const saving = ref(false)
const saved = ref(false)
const failed = ref(false)

async function save() {
  saving.value = true
  saved.value = false
  failed.value = false

  const payload = { features: {} }
  for (const key of FEATURE_KEYS) {
    if (isLocked(key)) continue
    payload.features[key] = values[key] === 'on' ? true : values[key] === 'off' ? false : null
  }
  if (customFieldsEnabled.value) payload.custom_fields = fieldsPayload()

  try {
    await groupsStore.updateOptions(props.group.id, payload)
    saved.value = true
  } catch {
    failed.value = true
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="group-options-card" data-testid="group-options-form" @submit.prevent="save">
    <h2 class="h4" data-testid="group-options-title">{{ t('client.features.group_title') }}</h2>

    <div v-for="key in FEATURE_KEYS" :key="key" class="mb-3">
      <label class="form-label" :for="`group-option-${key}`">{{ t(`client.features.${key}`) }}</label>
      <select
        :id="`group-option-${key}`"
        v-model="values[key]"
        class="form-select"
        :disabled="isLocked(key)"
        :data-testid="`group-option-${key}`"
      >
        <option v-if="!isLocked(key)" value="">{{ defaultLabel(key) }}</option>
        <option value="on">{{ t('client.features.group_on') }}</option>
        <option value="off">{{ t('client.features.group_off') }}</option>
      </select>
      <small v-if="isLocked(key)" class="form-text text-muted d-block" :data-testid="`group-option-${key}-locked`">{{ t('client.features.set_by', { networks: (features[key].set_by || []).join(', ') }) }}</small>
      <small v-else class="form-text text-muted d-block">{{ t(`client.features.${key}_help`) }}</small>
    </div>

    <div v-if="customFieldsEnabled" class="mb-3" data-testid="group-custom-fields">
      <h3 class="h5">{{ t('client.features.fields_title') }}</h3>

      <p v-if="!rows.length" class="text-muted small">{{ t('client.features.no_fields') }}</p>

      <div
        v-for="(row, i) in rows"
        :key="row.key || `new-${i}`"
        class="border rounded p-2 mb-2"
        :data-testid="`group-custom-field-row-${i}`"
      >
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label small" :for="`group-custom-field-label-${i}`">{{ t('client.features.field_label') }}</label>
            <input
              :id="`group-custom-field-label-${i}`"
              v-model="row.label"
              type="text"
              maxlength="50"
              class="form-control"
              :data-testid="`group-custom-field-label-${i}`"
            >
          </div>
          <div class="col-md-4">
            <label class="form-label small" :for="`group-custom-field-type-${i}`">{{ t('client.features.field_type') }}</label>
            <select
              :id="`group-custom-field-type-${i}`"
              v-model="row.type"
              class="form-select"
              :data-testid="`group-custom-field-type-${i}`"
            >
              <option value="text">{{ t('client.features.type_text') }}</option>
              <option value="number">{{ t('client.features.type_number') }}</option>
              <option value="choice">{{ t('client.features.type_choice') }}</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <BButton type="button" variant="outline-danger" size="sm" :data-testid="`group-custom-field-remove-${i}`" @click="removeField(i)">
              {{ t('client.features.remove_field') }}
            </BButton>
          </div>
        </div>
        <div v-if="row.type === 'choice'" class="mt-2">
          <label class="form-label small" :for="`group-custom-field-options-${i}`">{{ t('client.features.field_options') }}</label>
          <textarea
            :id="`group-custom-field-options-${i}`"
            v-model="row.optionsText"
            rows="3"
            class="form-control"
            :data-testid="`group-custom-field-options-${i}`"
          />
        </div>
      </div>

      <BButton
        type="button"
        variant="outline-primary"
        size="sm"
        :disabled="rows.length >= MAX_CUSTOM_FIELDS"
        data-testid="group-custom-field-add"
        @click="addField"
      >
        {{ t('client.features.add_field') }}
      </BButton>
      <small v-if="rows.length >= MAX_CUSTOM_FIELDS" class="form-text text-muted d-block">{{ t('client.features.max_fields', { max: MAX_CUSTOM_FIELDS }) }}</small>
      <small class="form-text text-muted d-block mt-1">{{ t('client.features.removing_note') }}</small>
    </div>

    <BAlert v-if="failed" :model-value="true" variant="danger" data-testid="group-options-error">
      {{ t('client.features.save_failed') }}
    </BAlert>
    <BAlert v-if="saved" :model-value="true" variant="success" data-testid="group-options-saved">
      {{ t('client.features.saved') }}
    </BAlert>

    <BButton type="submit" variant="primary" :disabled="saving" data-testid="group-options-save">
      {{ t('client.features.save') }}
    </BButton>
  </form>
</template>

<style scoped>
.group-options-card {
  background-color: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px #222;
  padding: 20px;
  margin: 0 0 30px 0;
}
</style>
