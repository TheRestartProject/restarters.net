<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Group-form-only stand-in for legacy GroupAddEdit.vue's vue-multiselect
// widgets (findings/parity-v2/group-forms.md #6): a searchable dropdown with
// removable chip-style selected items, optionally grouped under headings
// (mirrors GroupAddEdit.vue's groupedTagOptions - "Global" group first, then
// the rest alphabetically). There's no Vue 3 / bootstrap-vue-next
// equivalent of vue-multiselect already in this project, so this is a small
// bespoke control rather than a new npm dependency.
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  // [{ value, text, group? }] - `group` is optional; options without it
  // render in a single ungrouped list (used for the Networks selector).
  options: {
    type: Array,
    required: true,
  },
  testid: {
    type: String,
    default: 'group-multiselect',
  },
  placeholder: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const search = ref('')
const open = ref(false)

const selectedOptions = computed(() =>
  props.modelValue.map((value) => props.options.find((option) => option.value === value)).filter(Boolean),
)

const availableOptions = computed(() => {
  const term = search.value.trim().toLowerCase()
  return props.options.filter(
    (option) => !props.modelValue.includes(option.value) && (!term || option.text.toLowerCase().includes(term)),
  )
})

// "Global" first, then remaining groups alphabetically - matches
// GroupAddEdit.vue's groupedTagOptions computed exactly. Ungrouped options
// (group is null/undefined, e.g. Networks) come back as a single entry with
// a null label, rendered without a heading.
const groupedAvailableOptions = computed(() => {
  const groups = new Map()

  for (const option of availableOptions.value) {
    const label = option.group || null
    if (!groups.has(label)) groups.set(label, [])
    groups.get(label).push(option)
  }

  const result = []

  if (groups.has('Global')) {
    result.push({ label: 'Global', options: groups.get('Global') })
    groups.delete('Global')
  }

  for (const label of Array.from(groups.keys()).sort((a, b) => (a || '').localeCompare(b || ''))) {
    result.push({ label, options: groups.get(label) })
  }

  return result
})

function select(option) {
  emit('update:modelValue', [...props.modelValue, option.value])
  search.value = ''
}

function remove(value) {
  emit(
    'update:modelValue',
    props.modelValue.filter((v) => v !== value),
  )
}
</script>

<template>
  <div class="group-multiselect" :data-testid="testid">
    <div v-if="selectedOptions.length" class="group-multiselect__chips">
      <span
        v-for="option in selectedOptions"
        :key="option.value"
        class="badge group-multiselect__chip"
        :data-testid="`${testid}-chip-${option.value}`"
      >
        {{ option.text }}
        <button
          type="button"
          class="group-multiselect__chip-remove"
          :aria-label="t('partials.remove')"
          :data-testid="`${testid}-remove-${option.value}`"
          @click="remove(option.value)"
        >
          &times;
        </button>
      </span>
    </div>
    <input
      v-model="search"
      type="text"
      class="form-control"
      :placeholder="placeholder"
      :data-testid="`${testid}-search`"
      @focus="open = true"
      @blur="open = false"
    >
    <ul v-if="open && availableOptions.length" class="list-group group-multiselect__dropdown" :data-testid="`${testid}-dropdown`">
      <template v-for="group in groupedAvailableOptions" :key="group.label || 'ungrouped'">
        <li v-if="group.label" class="list-group-item disabled small text-muted fw-bold">{{ group.label }}</li>
        <li
          v-for="option in group.options"
          :key="option.value"
          class="list-group-item list-group-item-action"
          :data-testid="`${testid}-option-${option.value}`"
          @mousedown.prevent="select(option)"
        >
          {{ option.text }}
        </li>
      </template>
    </ul>
  </div>
</template>

<style scoped lang="scss">
.group-multiselect {
  position: relative;
}

.group-multiselect__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-bottom: 0.5rem;
}

// Matches develop's global .multiselect__tag styling (resources/sass/
// _global.scss's ".multiselect .multiselect__tags .multiselect__tag") -
// brand-orange fill, black border, hard drop shadow, square corners -
// rather than an invented teal/white rounded badge.
.group-multiselect__chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background-color: #ffbe5f;
  color: #222;
  font-weight: normal;
  padding: 0.35rem 0.5rem;
  border: 2px solid #000;
  border-radius: 0;
  box-shadow: 2px 2px 0 0 #222;
}

.group-multiselect__chip-remove {
  background: none;
  border: 0;
  color: inherit;
  line-height: 1;
  padding: 0;
}

.group-multiselect__dropdown {
  position: absolute;
  z-index: 1050;
  width: 100%;
  max-height: 250px;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
</style>
