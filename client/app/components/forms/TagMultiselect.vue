<script setup>
import { computed, nextTick, ref } from 'vue'

// Tag-chip multi-select, replacing the native <select multiple> that stood in
// for develop's vue-multiselect across the invite modals, device form, event
// form and filter bars.
//
// vue-multiselect is Vue 2 only. Freegle's Vue 3 client hit the same wall and
// answered it by building its own control on bootstrap-vue-next primitives
// (components/AutoComplete.vue) rather than adopting another dependency, so
// this follows that pattern.
//
// The markup deliberately reuses vue-multiselect's class names
// (.multiselect__tags, .multiselect__tag, .multiselect__content-wrapper,
// .multiselect__option--highlight) because develop styles those directly in
// resources/sass/_global.scss - square corners, a 3px #222 active border, and
// $brand-muted highlight/selected fills. Reusing the names keeps that styling
// meaningful instead of inventing a parallel look.
const props = defineProps({
  // Selected values. Array in multiple mode, scalar otherwise.
  modelValue: { type: [Array, String, Number, Object], default: () => [] },
  // Available options: strings, or objects addressed by trackBy/labelBy.
  options: { type: Array, default: () => [] },
  trackBy: { type: String, default: null },
  labelBy: { type: String, default: null },
  multiple: { type: Boolean, default: true },
  // Accept values typed by the user that aren't in `options` - develop's
  // manual-email box is taggable in exactly this way.
  taggable: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: undefined },
  // Marks entries the caller considers invalid (e.g. malformed emails), so the
  // chip can be flagged without this component knowing what it holds.
  invalidValues: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'tag'])

const search = ref('')
const open = ref(false)
const highlighted = ref(0)
const inputEl = ref(null)
const rootEl = ref(null)

const selected = computed(() => {
  if (props.multiple) return Array.isArray(props.modelValue) ? props.modelValue : []
  return props.modelValue === null || props.modelValue === undefined || props.modelValue === ''
    ? []
    : [props.modelValue]
})

function valueOf(option) {
  return props.trackBy && option && typeof option === 'object' ? option[props.trackBy] : option
}

function labelOf(value) {
  const match = props.options.find((o) => valueOf(o) === value)

  if (match && props.labelBy && typeof match === 'object') return match[props.labelBy]
  if (match && typeof match === 'object') return match[props.trackBy]

  // Taggable values have no matching option - show what the user typed.
  return match ?? value
}

const available = computed(() => {
  const term = search.value.trim().toLowerCase()

  return props.options.filter((o) => {
    if (props.multiple && selected.value.includes(valueOf(o))) return false
    if (!term) return true
    const text = props.labelBy && typeof o === 'object' ? o[props.labelBy] : String(valueOf(o))
    return String(text).toLowerCase().includes(term)
  })
})

function commit(next) {
  emit('update:modelValue', props.multiple ? next : (next[next.length - 1] ?? null))
}

function select(option) {
  const value = valueOf(option)

  if (props.multiple) {
    if (!selected.value.includes(value)) commit([...selected.value, value])
  } else {
    commit([value])
    open.value = false
  }

  search.value = ''
  highlighted.value = 0
}

function remove(value) {
  if (props.disabled) return
  commit(selected.value.filter((v) => v !== value))
}

// Free text becomes a value of its own. Emitted as well as committed so a
// caller can validate it (the invite modals flag malformed addresses).
function addTag() {
  const raw = search.value.trim().replace(/,+$/, '')
  if (!raw) return

  if (!selected.value.includes(raw)) {
    commit([...selected.value, raw])
    emit('tag', raw)
  }

  search.value = ''
}

function onEnter() {
  const option = available.value[highlighted.value]

  if (option !== undefined) {
    select(option)
  } else if (props.taggable) {
    addTag()
  }
}

function onBackspace() {
  // Only once the search box is empty, so backspace still edits text first.
  if (search.value === '' && selected.value.length) {
    remove(selected.value[selected.value.length - 1])
  }
}

function move(delta) {
  if (!available.value.length) return
  const next = highlighted.value + delta
  highlighted.value = (next + available.value.length) % available.value.length
}

async function focusInput() {
  if (props.disabled) return
  open.value = true
  await nextTick()
  inputEl.value?.focus()
}

function onBlur(event) {
  // Losing focus to our own dropdown is not leaving the control.
  if (rootEl.value?.contains(event.relatedTarget)) return

  if (props.taggable && search.value.trim()) addTag()
  open.value = false
}
</script>

<template>
  <div
    ref="rootEl"
    class="multiselect"
    :class="{ 'multiselect--active': open, 'multiselect--disabled': disabled }"
    data-testid="tag-multiselect"
  >
    <div class="multiselect__tags" @click="focusInput">
      <span
        v-for="value in selected"
        :key="String(value)"
        class="multiselect__tag"
        :class="{ 'multiselect__tag--invalid': invalidValues.includes(value) }"
        :data-testid="`tag-multiselect-tag-${value}`"
      >
        <span>{{ labelOf(value) }}</span>
        <i
          class="multiselect__tag-icon"
          :aria-label="`Remove ${labelOf(value)}`"
          role="button"
          tabindex="0"
          :data-testid="`tag-multiselect-remove-${value}`"
          @click.stop="remove(value)"
          @keydown.enter.prevent="remove(value)"
        />
      </span>

      <input
        :id="id"
        ref="inputEl"
        v-model="search"
        type="text"
        class="multiselect__input"
        autocomplete="off"
        :placeholder="selected.length ? '' : placeholder"
        :disabled="disabled"
        role="combobox"
        :aria-expanded="open"
        data-testid="tag-multiselect-input"
        @focus="open = true"
        @blur="onBlur"
        @keydown.enter.prevent="onEnter"
        @keydown.tab="taggable && search.trim() ? addTag() : null"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.delete="onBackspace"
        @keydown.esc="open = false"
      >
    </div>

    <div v-if="open && (available.length || (taggable && search.trim()))" class="multiselect__content-wrapper">
      <ul class="multiselect__content" role="listbox">
        <li
          v-for="(option, index) in available"
          :key="String(valueOf(option))"
          class="multiselect__element"
        >
          <span
            class="multiselect__option"
            :class="{ 'multiselect__option--highlight': index === highlighted }"
            role="option"
            :aria-selected="index === highlighted"
            :data-testid="`tag-multiselect-option-${valueOf(option)}`"
            @mousedown.prevent="select(option)"
            @mouseenter="highlighted = index"
          >
            {{ labelBy && typeof option === 'object' ? option[labelBy] : valueOf(option) }}
          </span>
        </li>
        <li v-if="taggable && search.trim() && !available.length" class="multiselect__element">
          <span
            class="multiselect__option multiselect__option--highlight"
            data-testid="tag-multiselect-add"
            @mousedown.prevent="addTag"
          >{{ search.trim() }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>
