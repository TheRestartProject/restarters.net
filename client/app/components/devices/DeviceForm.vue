<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { suggestDeviceCategory } from '../../composables/useDeviceCategorySuggestion.js'
import { fieldLabel } from '../../composables/useFeatureSwitches.js'
import DevicePhotos from './DevicePhotos.vue'
import FieldInfoPopover from '../forms/FieldInfoPopover.vue'

// Add/edit device form (api-contracts-phase-c.md C5; design.md §6.2 C5 task
// brief). Functional spec: resources/js/components/EventDevice.vue +
// DeviceType/DeviceCategorySelect/DeviceBrand/DeviceModel/DeviceAge/
// DeviceWeight/DeviceRepairStatus/DeviceProblem/DeviceNotes/DeviceQuantity.
// Payload field names match API\DeviceController::validateDeviceParams
// exactly (eventid/category/item_type/brand/model/age/estimate/problem/
// notes/repair_status/next_steps/spare_parts/barrier) - both
// createDevicev2 and updateDevicev2 read $request->input(...) directly, so
// extra/renamed keys are silently ignored, not rejected.
//
// vue-multiselect/vue-typeahead-bootstrap are Vue 2 only, so every legacy
// field built on them (item type, category, brand, repair status, next
// steps, spare parts, barrier) is ported here as a bespoke control that
// reproduces their markup/class names instead - components/forms/
// TagMultiselect.vue's own doc comment set this precedent first. See the
// `openDropdown` block below for the shared open/select/blur plumbing.
//
// Delete is NOT shown by default: DeviceRow.vue (the summary row this form
// replaces while editing) owns its own delete icon + confirm, matching
// EventDeviceSummary.vue's inline <EventDevice ... /> usage, which never
// passes `deleteButton` either. The optional `deleteButton`/`readonly`/
// `cancelButton` props below port EventDevice.vue's own props of the same
// name, for the OTHER legacy usage this form now also covers: gap fix
// (HIGH) - components/fixometer/DevicesSearchTable.vue's row-details
// panel, which (like FixometerRecordsTable.vue's row-details slot) renders
// this exact form disabled for a read-only viewer, or editable with an
// inline delete for an admin - :cancel-button="false" either way, since
// the row's own toggle icon already closes it.
const CATEGORY_MISC_POWERED = 46
const CATEGORY_MISC_UNPOWERED = 50

const STATUS_FIXED = 'Fixed'
const STATUS_REPAIRABLE = 'Repairable'
const STATUS_END_OF_LIFE = 'End of life'

const NEXT_STEPS_LABELS = {
  'More time needed': 'partials.more_time',
  'Professional help': 'partials.professional_help',
  'Do it yourself': 'partials.diy',
}
const SPARE_PARTS_LABELS = {
  'Third party': 'partials.yes_third_party',
  Manufacturer: 'partials.yes_manufacturer',
  No: 'partials.no',
}

const props = defineProps({
  eventId: {
    type: Number,
    required: true,
  },
  // Present (an existing Device resource) when editing; null when adding.
  device: {
    type: Object,
    default: null,
  },
  powered: {
    type: Boolean,
    required: true,
  },
  // View-only rendering (EventDevice.vue's `disabled`, i.e. `!edit && !add`):
  // every field disabled, Save/Cancel hidden entirely.
  readonly: {
    type: Boolean,
    default: false,
  },
  // Shows an inline Delete button (behind its own confirm modal) next to
  // Save - EventDevice.vue's `deleteButton` prop. Only meaningful while
  // editing.
  deleteButton: {
    type: Boolean,
    default: false,
  },
  cancelButton: {
    type: Boolean,
    default: true,
  },
  // Whether each field shows its (i) help popover; the repair records list turns them off.
  showInfo: {
    type: Boolean,
    default: true,
  },
  // The group's resolved feature switches and active custom item fields (the
  // event resource's group_features / group_custom_fields). Without them
  // (e.g. the repair records list) weights are on and there are no custom fields.
  groupFeatures: {
    type: Object,
    default: null,
  },
  groupCustomFields: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['saved', 'cancel', 'deleted'])

const { t } = useI18n()
const devicesStore = useDevicesStore()

const editing = computed(() => !!props.device)

// Item type's help text depends on whether the item is powered - the
// examples differ ('Blender'/'Drill' vs 'Sofa'/'Denim jeans').
const itemTypeTooltip = computed(() =>
  props.powered ? t('devices.tooltip_type_powered') : t('devices.tooltip_type_unpowered')
)

// Black while adding, green while editing: the edit form sits on the
// shaded .edit-panel, where a black icon disappears.
const infoIconVariant = computed(() => (editing.value ? 'brand' : 'black'))
const miscCategoryId = computed(() => (props.powered ? CATEGORY_MISC_POWERED : CATEGORY_MISC_UNPOWERED))

onMounted(() => {
  devicesStore.ensureMetaLoaded()
})

const itemTypes = computed(() => devicesStore.itemTypes.data)
const clusters = computed(() => devicesStore.clusters)
const brands = computed(() => devicesStore.brands.data)
const options = computed(() => devicesStore.options.data)

const form = reactive({
  itemType: props.device?.item_type || '',
  category: props.device?.category?.id ?? null,
  brand: props.device?.brand || '',
  model: props.device?.model || '',
  age: props.device?.age ? String(props.device.age) : '',
  // Never pre-filled with the category weight; no weight is an empty box.
  estimate: Number(props.device?.estimate) > 0 ? String(props.device.estimate) : '',
  problem: props.device?.problem || '',
  notes: props.device?.notes || '',
  reference: props.device?.reference || '',
  repairStatus: props.device?.repair_status || '',
  nextSteps: props.device?.next_steps || '',
  spareParts: props.device?.spare_parts || '',
  barrier: props.device?.barrier || '',
  quantity: 1,
  custom: { ...(props.device?.custom_values || {}) },
})

const quantities = Array.from({ length: 11 }, (_, i) => i)

// Ports EventDevice.vue's `suggestedCategory` watcher: for add, always
// apply the current suggestion (the user is still typing); for edit, only
// apply it if the category is empty (don't clobber a category the record
// already had, or one the user has since edited away from the suggestion).
const categorySuggested = ref(false)
let suggestionTimer = null

// Two triggers with different clearing semantics (the legacy implementation
// derived the suggestion from a computed over the Vuex store, so both cases
// worked implicitly):
// - user edits the item type: apply a matching suggestion, or clear the
//   category when nothing matches (legacy behavior for add);
// - the item-types/clusters datasets arrive after the user already typed:
//   apply a match, but NEVER clear - the user may have picked a category
//   manually while the data was still loading.
function runSuggestion(value, { allowClear }) {
  const suggestion = suggestDeviceCategory({
    itemType: value,
    powered: props.powered,
    clusters: clusters.value,
    itemTypes: itemTypes.value,
    translate: t,
  })

  if (suggestion) {
    if (!editing.value || !form.category) {
      form.category = suggestion.idcategories
      categorySuggested.value = true
      clearTimeout(suggestionTimer)
      suggestionTimer = setTimeout(() => {
        categorySuggested.value = false
      }, 5000)
    }
  } else if (allowClear && !editing.value) {
    form.category = null
  }
}

watch(
  () => form.itemType,
  (newVal) => runSuggestion(newVal, { allowClear: true })
)
watch([itemTypes, clusters], () => {
  if (form.itemType) runSuggestion(form.itemType, { allowClear: false })
})

// The weight box is shown for every item when the group's item-weight switch
// is on (the default), otherwise only for Misc items (powered or unpowered).
const isMisc = computed(() => form.category === miscCategoryId.value)
const weightSwitchOn = computed(() => props.groupFeatures?.item_weight !== false)
const showWeight = computed(() => weightSwitchOn.value || isMisc.value)

const selectedCategory = computed(() => devicesStore.categories.data.find((c) => c.id === form.category) || null)
const categoryName = computed(() => (selectedCategory.value ? t(selectedCategory.value.name) : ''))

// Accepts a decimal comma. null = blank, NaN = not a number.
function parseWeight(raw) {
  const text = String(raw ?? '').trim().replace(',', '.')
  if (text === '') return null
  return /^\d*\.?\d+$|^\d+\.$/.test(text) ? parseFloat(text) : NaN
}
const enteredWeight = computed(() => parseWeight(form.estimate))
const weightInvalid = ref(false)
// The most the database can store (devices.estimate is DECIMAL(6,3)).
const MAX_STORABLE_WEIGHT = 999.999

function formatKg(n) {
  return String(parseFloat(Number(n).toFixed(3)))
}

const typicalWeight = computed(() => {
  const w = Number(selectedCategory.value?.typical_weight)
  return w > 0 ? w : null
})
const weightHint = computed(() => {
  if (!selectedCategory.value) return ''
  if (isMisc.value) {
    return typicalWeight.value
      ? t('client.devices.weight_hint_misc', { weight: formatKg(typicalWeight.value) })
      : t('client.devices.weight_hint_misc_none')
  }
  return typicalWeight.value
    ? t('client.devices.weight_hint', { weight: formatKg(typicalWeight.value), category: categoryName.value })
    : ''
})

// Grams typed as kilograms: a weight whose thousandth is within 3 times the
// usual weight (the same test used to spot them in live data).
function gramsSuggestion(v) {
  const grams = v / 1000
  const typicalKg = typicalWeight.value
  if (!typicalKg || grams < typicalKg / 3 || grams > typicalKg * 3) return ''
  return ` ${t('client.devices.weight_did_you_mean', { value: formatKg(grams) })}`
}

// A weight the database can't store blocks saving; it is shown as soon as it is typed.
const weightTooHeavy = computed(() => {
  const v = enteredWeight.value
  if (!showWeight.value || v === null || Number.isNaN(v) || v <= MAX_STORABLE_WEIGHT) return ''
  return t('client.devices.weight_too_heavy') + gramsSuggestion(v)
})
const weightError = computed(() => (weightInvalid.value ? t('client.devices.weight_invalid') : weightTooHeavy.value))

// A weight outside the category's range will not count towards impact figures.
// It is a warning only: the item can still be saved.
const weightWarning = computed(() => {
  const cat = selectedCategory.value
  const v = enteredWeight.value
  if (!cat || v === null || Number.isNaN(v) || v <= 0 || !typicalWeight.value) return ''

  const min = cat.weight_min ?? null
  const max = cat.weight_max ?? null
  const typical = formatKg(typicalWeight.value)
  let message = ''

  if (max !== null && v > max) {
    message = isMisc.value
      ? t('client.devices.weight_warning_high_misc', { typical })
      : t('client.devices.weight_warning_high', { category: categoryName.value, typical })
  } else if (!isMisc.value && min !== null && v < min) {
    message = t('client.devices.weight_warning_low', { category: categoryName.value, typical })
  }
  if (!message || v > MAX_STORABLE_WEIGHT) return ''

  if (max !== null && v > max) {
    message += gramsSuggestion(v)
  }
  return message
})

// Custom item fields come after the standard fields, when the group has them switched on.
const customFields = computed(() => (props.groupFeatures?.custom_fields ? props.groupCustomFields || [] : []))
const originalCustom = props.device?.custom_values || {}

function customValuesPayload() {
  const out = {}
  for (const f of customFields.value) {
    const raw = form.custom[f.key]
    const value = raw === '' || raw === undefined || raw === null ? null : raw
    if (value !== null) out[f.key] = value
    else if (originalCustom[f.key] !== undefined && originalCustom[f.key] !== null) out[f.key] = null
  }
  return out
}

const showNextSteps = computed(() => form.repairStatus === STATUS_REPAIRABLE)
const showSpareParts = computed(() => form.repairStatus === STATUS_REPAIRABLE || form.repairStatus === STATUS_FIXED)
const showBarrier = computed(() => form.repairStatus === STATUS_END_OF_LIFE)

const itemTypeSuggestions = computed(() =>
  itemTypes.value.filter((i) => i.type && Boolean(i.powered) === Boolean(props.powered)).map((i) => i.type)
)

// Ports DeviceType.vue/DeviceBrand.vue's `notASuggestion` warnings: flag a
// typed value that isn't one of the known suggestions. Skipped when it
// still matches the value the device was saved with, so opening an
// existing device that already has an off-list value (legacy free text)
// doesn't immediately nag before the user has changed anything.
const savedItemType = props.device?.item_type || ''
const savedBrand = props.device?.brand || ''

const itemTypeUnknown = computed(() => {
  if (!form.itemType || form.itemType === savedItemType) return false
  return !itemTypeSuggestions.value.includes(form.itemType)
})

const brandUnknown = computed(() => {
  if (!form.brand || form.brand === savedBrand) return false
  return !brands.value.some((b) => b.brand_name === form.brand)
})

// DeviceCategorySelect.vue: only the categories matching the device's
// powered/unpowered type, minus this type's misc category (it's offered once
// at the end as "None of the above"). Filtering by type also drops the other
// type's misc, which would otherwise show up as a second "Misc".
function categoryOptionsForCluster(cluster) {
  return (cluster.categories || []).filter(
    (c) => c.idcategories !== miscCategoryId.value && Boolean(c.powered) === Boolean(props.powered),
  )
}

function nextStepLabel(value) {
  return NEXT_STEPS_LABELS[value] || value
}
function sparePartsLabel(value) {
  return SPARE_PARTS_LABELS[value] || value
}

// Gap fix (control-substitution finding): develop drives item type/category/
// brand/repair status/next steps/spare parts/barrier with vue-multiselect
// (select-type fields) and vue-typeahead-bootstrap (item type/brand) -
// clickable custom panels, not the browser's own <select>/<datalist> chrome.
// components/forms/TagMultiselect.vue already established this project's
// answer for the same Vue-2-only-library problem (see its own doc comment):
// reproduce vue-multiselect's markup/class names
// (.multiselect/.multiselect__tags/.multiselect__content-wrapper/
// .multiselect__option, styled globally in assets/css/_multiselect.scss)
// on plain elements rather than adding a dependency. `openDropdown` tracks
// which single field's panel is open at a time (mousedown.prevent on each
// option, same as TagMultiselect, so choosing an option never fires the
// control's blur first).
const openDropdown = ref(null)

function toggleDropdownFor(key) {
  if (props.readonly) return
  const opening = openDropdown.value !== key
  openDropdown.value = opening ? key : null
  // DeviceCategorySelect.vue's `@open="suggested = false"`: opening the
  // category panel yourself cancels the auto-suggestion highlight.
  if (opening && key === 'category') categorySuggested.value = false
}

function closeDropdown(key) {
  if (openDropdown.value === key) openDropdown.value = null
}

function onFieldBlur(key, event) {
  // Losing focus to our own panel (an option's mousedown is prevented, so
  // this only fires for a genuine focus move elsewhere) closes it.
  if (event.currentTarget.contains(event.relatedTarget)) return
  closeDropdown(key)
}

const categorySelectedLabel = computed(() => {
  if (form.category === miscCategoryId.value) return t('partials.category_none')
  for (const cluster of clusters.value) {
    const match = categoryOptionsForCluster(cluster).find((c) => c.idcategories === form.category)
    if (match) return t(match.name)
  }
  return ''
})

function selectCategory(value) {
  form.category = value
  openDropdown.value = null
}

const statusOptions = computed(() => [
  { value: STATUS_FIXED, label: t('partials.fixed') },
  { value: STATUS_REPAIRABLE, label: t('partials.repairable') },
  { value: STATUS_END_OF_LIFE, label: t('partials.end_of_life') },
])
const statusSelectedLabel = computed(() => statusOptions.value.find((o) => o.value === form.repairStatus)?.label || '')

// Choosing the option that is already selected clears it, as vue-multiselect does when empty is allowed, so a
// wrong choice can be undone.  The category is required, so it can't be cleared.
function chooseOrClear(current, value) {
  return current === value ? '' : value
}

function selectStatus(value) {
  form.repairStatus = chooseOrClear(form.repairStatus, value)
  openDropdown.value = null
}

const nextStepsOptions = computed(() => (options.value.next_steps || []).map((n) => ({ value: n, label: t(nextStepLabel(n)) })))
const nextStepsSelectedLabel = computed(() => nextStepsOptions.value.find((o) => o.value === form.nextSteps)?.label || '')
function selectNextSteps(value) {
  form.nextSteps = chooseOrClear(form.nextSteps, value)
  openDropdown.value = null
}

const sparePartsOptions = computed(() => (options.value.spare_parts || []).map((p) => ({ value: p, label: t(sparePartsLabel(p)) })))
const sparePartsSelectedLabel = computed(() => sparePartsOptions.value.find((o) => o.value === form.spareParts)?.label || '')
function selectSpareParts(value) {
  form.spareParts = chooseOrClear(form.spareParts, value)
  openDropdown.value = null
}

const barrierOptions = computed(() => (options.value.barriers || []).map((b) => ({ value: b.name, label: t(b.name) })))
const barrierSelectedLabel = computed(() => barrierOptions.value.find((o) => o.value === form.barrier)?.label || '')
function selectBarrier(value) {
  form.barrier = chooseOrClear(form.barrier, value)
  openDropdown.value = null
}

// Item type/brand: a real <input> (so typed text is directly editable, as
// in vue-typeahead-bootstrap) plus a custom-styled suggestion panel below it
// instead of the browser's own <datalist> popup. Matching follows
// vue-typeahead-bootstrap as DeviceType.vue/DeviceBrand.vue configured it
// (`:minMatchingChars="1" :maxMatches="5"`): nothing until something is
// typed, case-insensitive substring match, then a stable sort by where the
// match starts - so "Kettle lead" comes before "Electric kettle".
function typeaheadMatches(list, query) {
  if (!query) return []
  const term = query.toLowerCase()
  return list
    .map((text, i) => ({ text, i, pos: text.toLowerCase().indexOf(term) }))
    .filter((m) => m.pos !== -1)
    .sort((a, b) => a.pos - b.pos || a.i - b.i)
    .slice(0, 5)
    .map((m) => m.text)
}

const itemTypeMatches = computed(() => typeaheadMatches(itemTypeSuggestions.value, form.itemType))
function selectItemType(value) {
  form.itemType = value
  openDropdown.value = null
}

const brandNames = computed(() => brands.value.map((b) => b.brand_name))
const brandMatches = computed(() => typeaheadMatches(brandNames.value, form.brand))
function selectBrand(value) {
  form.brand = value
  openDropdown.value = null
}

const submitting = ref(false)
const missingCategory = ref(false)
const generalError = ref('')

function buildPayload() {
  // The v2 device endpoint's validation (a pre-existing contract) accepts
  // most optional fields as omit-or-string, NOT null - `'brand' => 'string'`
  // etc. fail on null (same class as the group-form postcode bug). Only
  // 'problem'/'next_steps'/'barrier' are nullable server-side, but omitting
  // empties uniformly is simplest and matches what the legacy client sent.
  const payload = {
    category: form.category,
    age: form.age ? parseFloat(form.age) : 0,
    estimate: enteredWeight.value,
  }

  const optional = {
    item_type: form.itemType,
    brand: form.brand,
    model: form.model,
    problem: form.problem,
    notes: form.notes,
    reference: form.reference,
    repair_status: form.repairStatus,
    next_steps: showNextSteps.value ? form.nextSteps : '',
    spare_parts: showSpareParts.value ? form.spareParts : '',
    barrier: showBarrier.value ? form.barrier : '',
  }
  for (const [key, value] of Object.entries(optional)) {
    if (value) payload[key] = value
  }
  if (customFields.value.length) payload.custom_values = customValuesPayload()

  return payload
}

async function submit() {
  generalError.value = ''

  if (!form.category) {
    missingCategory.value = true
    return
  }
  missingCategory.value = false

  weightInvalid.value = showWeight.value && Number.isNaN(enteredWeight.value)
  if (weightInvalid.value || weightTooHeavy.value) return

  submitting.value = true

  const payload = buildPayload()

  try {
    if (editing.value) {
      const { device } = await devicesStore.updateDevice(props.eventId, props.device.id, payload)
      emit('saved', device)
    } else {
      // The API only creates one device per call - loop client-side for
      // quantity>1, same as EventDevice.vue's addDevice(). Fired
      // concurrently (not awaited one-by-one) since each create is
      // independent.
      const creates = Array.from({ length: form.quantity }, () => devicesStore.addDevice(props.eventId, payload))
      const results = await Promise.all(creates)
      emit('saved', results[results.length - 1]?.device)
    }
  } catch {
    generalError.value = editing.value ? t('client.devices.edit_failed') : t('client.devices.add_failed')
  } finally {
    submitting.value = false
  }
}

function cancel() {
  emit('cancel')
}

// EventDevice.vue's confirmDeleteDevice()/deleteDevice(): a ConfirmModal
// gate before the actual delete, same idiom as DevicesSearchTable.vue's
// (now-removed) admin delete modal and components/admin/AdminCrudTable.vue.
const confirmingDelete = ref(false)
const deleting = ref(false)

function askDelete() {
  confirmingDelete.value = true
}

function cancelDeleteConfirm() {
  confirmingDelete.value = false
}

async function confirmDeleteDevice() {
  confirmingDelete.value = false
  deleting.value = true
  generalError.value = ''

  try {
    await devicesStore.deleteDevice(props.eventId, props.device.id)
    emit('deleted')
  } catch {
    generalError.value = t('client.devices.delete_failed')
  } finally {
    deleting.value = false
  }
}

defineExpose({ submit })

// The device reference (PR #907) isn't on the live site yet; hidden until the
// rest of the Nuxt client matches live. Flip to true to bring it back -
// stored values are untouched either way.
const SHOW_DEVICE_REFERENCE = false
</script>

<template>
  <BForm data-testid="device-form" @submit.prevent="submit">
    <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="device-form-error">
      {{ generalError }}
    </BAlert>

    <!-- develop's EventDevice.vue: three bordered cards (Item/Repair/
         Assessment) on a row, rather than one flat form. -->
    <div class="device-form-grid">
      <div class="device-form-card" data-testid="device-form-card-item">
        <h3>{{ t('devices.title_items') }}</h3>

        <BFormGroup label-for="device-form-item-type">
          <template #label>
            {{ t('devices.item_type') }}:
            <FieldInfoPopover v-if="showInfo" :content="itemTypeTooltip" :variant="infoIconVariant" />
          </template>
          <div class="position-relative typeahead-field">
            <input
              id="device-form-item-type"
              v-model="form.itemType"
              type="text"
              class="form-control form-control-lg"
              autocomplete="off"
              :disabled="readonly"
              data-testid="device-form-item-type"
              @focus="openDropdown = 'itemType'"
              @blur="onFieldBlur('itemType', $event)"
            >
            <div
              v-if="openDropdown === 'itemType' && itemTypeMatches.length"
              class="multiselect__content-wrapper"
              data-testid="device-form-item-type-suggestions"
            >
              <ul class="multiselect__content">
                <li
                  v-for="s in itemTypeMatches"
                  :key="s"
                  class="multiselect__option"
                  :data-testid="`device-form-item-type-option-${s}`"
                  @mousedown.prevent="selectItemType(s)"
                >
                  {{ s }}
                </li>
              </ul>
            </div>
          </div>
          <small v-if="itemTypeUnknown" class="form-text text-warning d-block" data-testid="device-form-item-type-warning">
            {{ t('devices.unknown_item_type') }}
          </small>
        </BFormGroup>

        <BFormGroup label-for="device-form-category">
          <template #label>
            {{ t('devices.category') }}:
            <FieldInfoPopover v-if="showInfo" :content="t('devices.tooltip_category')" :variant="infoIconVariant" />
          </template>
          <div class="device-category" :class="{ suggested: categorySuggested, 'border-thick': missingCategory }">
            <div
              id="device-form-category"
              class="multiselect"
              :class="{ 'multiselect--active': openDropdown === 'category', 'multiselect--disabled': readonly }"
            >
              <div
                class="multiselect__tags"
                tabindex="0"
                :disabled="readonly"
                data-testid="device-form-category"
                :data-value="form.category === null ? '' : String(form.category)"
                @click="toggleDropdownFor('category')"
                @keydown.esc="closeDropdown('category')"
                @keydown.enter.prevent="toggleDropdownFor('category')"
                @blur="onFieldBlur('category', $event)"
              >
                {{ categorySelectedLabel || t('devices.category') }}
              </div>
              <div v-if="openDropdown === 'category'" class="multiselect__content-wrapper">
                <ul class="multiselect__content">
                  <template v-for="cluster in clusters" :key="cluster.id">
                    <li
                      v-if="categoryOptionsForCluster(cluster).length"
                      class="multiselect__option multiselect__option--group multiselect__option--disabled"
                    >
                      {{ t(cluster.name) }}
                    </li>
                    <li
                      v-for="c in categoryOptionsForCluster(cluster)"
                      :key="c.idcategories"
                      class="multiselect__option"
                      :class="{ 'multiselect__option--selected': form.category === c.idcategories }"
                      :data-testid="`device-form-category-option-${c.idcategories}`"
                      @mousedown.prevent="selectCategory(c.idcategories)"
                    >
                      {{ t(c.name) }}
                    </li>
                  </template>
                  <li
                    class="multiselect__option"
                    :class="{ 'multiselect__option--selected': form.category === miscCategoryId }"
                    :data-testid="`device-form-category-option-${miscCategoryId}`"
                    @mousedown.prevent="selectCategory(miscCategoryId)"
                  >
                    {{ t('partials.category_none') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div v-if="missingCategory" class="invalid-feedback d-block" data-testid="device-form-category-error">
            {{ t('events.form_error') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('devices.brand')}:`" label-for="device-form-brand">
          <div class="position-relative typeahead-field">
            <input
              id="device-form-brand" v-model="form.brand"
              :aria-label="`${t('devices.brand')}`"
              type="text"
              class="form-control form-control-lg"
              autocomplete="off"
              :placeholder="t('devices.brand_if_known')"
              :disabled="readonly"
              data-testid="device-form-brand"
              @focus="openDropdown = 'brand'"
              @blur="onFieldBlur('brand', $event)"
            >
            <div
              v-if="openDropdown === 'brand' && brandMatches.length"
              class="multiselect__content-wrapper"
              data-testid="device-form-brand-suggestions"
            >
              <ul class="multiselect__content">
                <li
                  v-for="b in brandMatches"
                  :key="b"
                  class="multiselect__option"
                  :data-testid="`device-form-brand-option-${b}`"
                  @mousedown.prevent="selectBrand(b)"
                >
                  {{ b }}
                </li>
              </ul>
            </div>
          </div>
          <small v-if="brandUnknown" class="form-text text-warning d-block" data-testid="device-form-brand-warning">
            {{ t('devices.unknown_brand') }}
          </small>
        </BFormGroup>

        <BFormGroup label-for="device-form-model">
          <template #label>
            {{ t('devices.model') }}:
            <FieldInfoPopover v-if="showInfo" :content="t('devices.tooltip_model')" :variant="infoIconVariant" />
          </template>
          <input
            id="device-form-model"
            v-model="form.model"
            :aria-label="t('devices.model')"
            type="text"
            class="form-control"
            :placeholder="t('devices.model_if_known')"
            :disabled="readonly"
            data-testid="device-form-model"
          >
        </BFormGroup>

        <BFormGroup v-if="SHOW_DEVICE_REFERENCE" label-for="device-form-reference">
          <template #label>
            {{ t('devices.reference') }}:
            <FieldInfoPopover v-if="showInfo" :content="t('devices.tooltip_reference')" :variant="infoIconVariant" />
          </template>
          <input
            id="device-form-reference"
            v-model="form.reference"
            type="text"
            maxlength="255"
            autocomplete="off"
            class="form-control"
            :disabled="readonly"
            data-testid="device-form-reference"
          >
        </BFormGroup>

        <BFormGroup :label="`${t('devices.age')}:`" label-for="device-form-age">
          <input
            id="device-form-age" v-model="form.age"
            :aria-label="`${t('devices.age')}`"
            type="number"
            min="0"
            step="0.5"
            max="500"
            class="form-control"
            :disabled="readonly"
            data-testid="device-form-age"
          >
          <small class="form-text text-muted">{{ t('devices.age_approx') }}</small>
        </BFormGroup>

        <BFormGroup v-if="showWeight" :label="`${t('devices.weight')} (kg):`" label-for="device-form-weight">
          <input
            id="device-form-weight" v-model="form.estimate"
            :aria-label="`${t('devices.weight')}`"
            type="text"
            inputmode="decimal"
            class="form-control"
            :class="{ 'is-invalid': weightError }"
            :disabled="readonly"
            data-testid="device-form-weight"
          >
          <small v-if="weightError" class="form-text text-danger d-block" data-testid="device-form-weight-error">
            {{ weightError }}
          </small>
          <small v-if="weightHint" class="form-text text-muted d-block" data-testid="device-form-weight-hint">
            {{ weightHint }}
          </small>
          <small v-if="weightWarning" class="form-text text-warning-emphasis d-block" role="alert" data-testid="device-form-weight-warning">
            {{ weightWarning }}
          </small>
        </BFormGroup>

        <DevicePhotos v-if="editing" :event-id="eventId" :device-id="device.id" :images="device.images || []" :readonly="readonly" />
      </div>

      <div class="device-form-card" data-testid="device-form-card-repair">
        <h3>{{ t('devices.title_repair') }}</h3>

        <BFormGroup :label="`${t('devices.repair_status')}:`" label-for="device-form-status">
          <div
            id="device-form-status"
            class="multiselect"
            :class="{ 'multiselect--active': openDropdown === 'status', 'multiselect--disabled': readonly }"
          >
            <div
              class="multiselect__tags"
              tabindex="0"
              :disabled="readonly"
              data-testid="device-form-status"
              :data-value="form.repairStatus"
              @click="toggleDropdownFor('status')"
              @keydown.esc="closeDropdown('status')"
              @keydown.enter.prevent="toggleDropdownFor('status')"
              @blur="onFieldBlur('status', $event)"
            >
              {{ statusSelectedLabel || t('devices.repair_outcome') }}
            </div>
            <div v-if="openDropdown === 'status'" class="multiselect__content-wrapper">
              <ul class="multiselect__content">
                <li
                  v-for="o in statusOptions"
                  :key="o.value"
                  class="multiselect__option"
                  :class="{ 'multiselect__option--selected': form.repairStatus === o.value }"
                  :data-testid="`device-form-status-option-${o.value}`"
                  @mousedown.prevent="selectStatus(o.value)"
                >
                  {{ o.label }}
                </li>
              </ul>
            </div>
          </div>
        </BFormGroup>

        <BFormGroup v-if="showNextSteps" :label="`${t('devices.repair_details')}:`" label-for="device-form-next-steps">
          <div
            id="device-form-next-steps"
            class="multiselect"
            :class="{ 'multiselect--active': openDropdown === 'nextSteps', 'multiselect--disabled': readonly }"
          >
            <div
              class="multiselect__tags"
              tabindex="0"
              :disabled="readonly"
              data-testid="device-form-next-steps"
              :data-value="form.nextSteps"
              @click="toggleDropdownFor('nextSteps')"
              @keydown.esc="closeDropdown('nextSteps')"
              @keydown.enter.prevent="toggleDropdownFor('nextSteps')"
              @blur="onFieldBlur('nextSteps', $event)"
            >
              {{ nextStepsSelectedLabel || t('devices.repair_details') }}
            </div>
            <div v-if="openDropdown === 'nextSteps'" class="multiselect__content-wrapper">
              <ul class="multiselect__content">
                <li
                  v-for="o in nextStepsOptions"
                  :key="o.value"
                  class="multiselect__option"
                  :class="{ 'multiselect__option--selected': form.nextSteps === o.value }"
                  :data-testid="`device-form-next-steps-option-${o.value}`"
                  @mousedown.prevent="selectNextSteps(o.value)"
                >
                  {{ o.label }}
                </li>
              </ul>
            </div>
          </div>
        </BFormGroup>

        <BFormGroup v-if="showSpareParts" :label="`${t('devices.spare_parts')}:`" label-for="device-form-spare-parts">
          <div
            id="device-form-spare-parts"
            class="multiselect"
            :class="{ 'multiselect--active': openDropdown === 'spareParts', 'multiselect--disabled': readonly }"
          >
            <div
              class="multiselect__tags"
              tabindex="0"
              :disabled="readonly"
              data-testid="device-form-spare-parts"
              :data-value="form.spareParts"
              @click="toggleDropdownFor('spareParts')"
              @keydown.esc="closeDropdown('spareParts')"
              @keydown.enter.prevent="toggleDropdownFor('spareParts')"
              @blur="onFieldBlur('spareParts', $event)"
            >
              {{ sparePartsSelectedLabel || t('devices.spare_parts') }}
            </div>
            <div v-if="openDropdown === 'spareParts'" class="multiselect__content-wrapper">
              <ul class="multiselect__content">
                <li
                  v-for="o in sparePartsOptions"
                  :key="o.value"
                  class="multiselect__option"
                  :class="{ 'multiselect__option--selected': form.spareParts === o.value }"
                  :data-testid="`device-form-spare-parts-option-${o.value}`"
                  @mousedown.prevent="selectSpareParts(o.value)"
                >
                  {{ o.label }}
                </li>
              </ul>
            </div>
          </div>
        </BFormGroup>

        <BFormGroup v-if="showBarrier" :label="`${t('partials.choose_barriers')}:`" label-for="device-form-barrier">
          <div
            id="device-form-barrier"
            class="multiselect"
            :class="{ 'multiselect--active': openDropdown === 'barrier', 'multiselect--disabled': readonly }"
          >
            <div
              class="multiselect__tags"
              tabindex="0"
              :disabled="readonly"
              data-testid="device-form-barrier"
              :data-value="form.barrier"
              @click="toggleDropdownFor('barrier')"
              @keydown.esc="closeDropdown('barrier')"
              @keydown.enter.prevent="toggleDropdownFor('barrier')"
              @blur="onFieldBlur('barrier', $event)"
            >
              {{ barrierSelectedLabel || t('partials.choose_barriers') }}
            </div>
            <div v-if="openDropdown === 'barrier'" class="multiselect__content-wrapper">
              <ul class="multiselect__content">
                <li
                  v-for="o in barrierOptions"
                  :key="o.value"
                  class="multiselect__option"
                  :class="{ 'multiselect__option--selected': form.barrier === o.value }"
                  :data-testid="`device-form-barrier-option-${o.value}`"
                  @mousedown.prevent="selectBarrier(o.value)"
                >
                  {{ o.label }}
                </li>
              </ul>
            </div>
          </div>
        </BFormGroup>
      </div>

      <div class="device-form-card" data-testid="device-form-card-assessment">
        <h3>{{ t('devices.title_assessment') }}</h3>

        <BFormGroup label-for="device-form-problem">
          <template #label>
            {{ t('devices.devices_description') }}:
            <FieldInfoPopover v-if="showInfo" :content="t('devices.tooltip_problem')" :variant="infoIconVariant" />
          </template>
          <textarea
            id="device-form-problem"
            v-model="form.problem"
            :aria-label="t('devices.devices_description')"
            rows="4"
            class="form-control"
            :disabled="readonly"
            data-testid="device-form-problem"
          />
        </BFormGroup>

        <BFormGroup label-for="device-form-notes">
          <template #label>
            {{ t('client.devices.notes') }}:
            <FieldInfoPopover v-if="showInfo" :content="t('devices.tooltip_notes')" :variant="infoIconVariant" />
          </template>
          <textarea
            id="device-form-notes"
            v-model="form.notes"
            :aria-label="t('client.devices.notes')"
            rows="4"
            class="form-control"
            :placeholder="t('devices.placeholder_notes')"
            :disabled="readonly"
            data-testid="device-form-notes"
          />
        </BFormGroup>
      </div>
    </div>

    <div v-if="customFields.length" class="device-form-card" data-testid="device-form-custom-fields">
      <h3>{{ t('client.devices.custom_fields_heading') }}</h3>
      <BFormGroup v-for="f in customFields" :key="f.key" :label="fieldLabel(f.label)" :label-for="`device-form-custom-${f.key}`">
        <select
          v-if="f.type === 'choice'"
          :id="`device-form-custom-${f.key}`"
          v-model="form.custom[f.key]"
          class="form-select"
          :disabled="readonly"
          :data-testid="`device-form-custom-${f.key}`"
        >
          <option value="" />
          <option v-for="o in f.options" :key="o" :value="o">{{ o }}</option>
        </select>
        <input
          v-else-if="f.type === 'number'"
          :id="`device-form-custom-${f.key}`"
          v-model.number="form.custom[f.key]"
          type="number"
          step="any"
          class="form-control"
          :disabled="readonly"
          :data-testid="`device-form-custom-${f.key}`"
        >
        <input
          v-else
          :id="`device-form-custom-${f.key}`"
          v-model="form.custom[f.key]"
          type="text"
          maxlength="255"
          class="form-control"
          :disabled="readonly"
          :data-testid="`device-form-custom-${f.key}`"
        >
      </BFormGroup>
    </div>

    <BFormGroup v-if="!editing" :label="`${t('client.devices.quantity')}:`" label-for="device-form-quantity">
      <select id="device-form-quantity" v-model.number="form.quantity" :aria-label="`${t('client.devices.quantity')}`" class="form-select w-auto" data-testid="device-form-quantity">
        <option v-for="q in quantities" :key="q" :value="q">{{ q }}</option>
      </select>
    </BFormGroup>

    <div v-if="!readonly || (editing && deleteButton)" class="d-flex gap-2 justify-content-center mt-3">
      <BButton v-if="!readonly" type="submit" variant="primary" :disabled="submitting" data-testid="device-form-submit">
        {{ editing ? t('partials.save') : t('partials.add_device') }}
      </BButton>
      <BButton v-if="editing && deleteButton" variant="danger" data-testid="device-form-delete" @click="askDelete">
        {{ t('devices.delete_device') }}
      </BButton>
      <BButton v-if="cancelButton && !readonly" variant="tertiary" data-testid="device-form-cancel" @click="cancel">
        {{ t('partials.cancel') }}
      </BButton>
    </div>

    <BModal
      v-if="editing && deleteButton"
      :model-value="confirmingDelete"
      :title="t('devices.delete_device')"
      no-footer
      data-testid="device-form-delete-modal"
      @hide="cancelDeleteConfirm"
    >
      <p>{{ t('devices.confirm_delete') }}</p>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="outline-secondary" data-testid="device-form-delete-cancel" @click="cancelDeleteConfirm">
          {{ t('partials.cancel') }}
        </BButton>
        <BButton variant="danger" :disabled="deleting" data-testid="device-form-delete-confirm" @click="confirmDeleteDevice">
          {{ t('devices.delete_device') }}
        </BButton>
      </div>
    </BModal>
  </BForm>
</template>

<style scoped>
.device-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.device-form-card {
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  padding: 1rem;
}

.device-form-card h3 {
  font-size: 1.1rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

/* Gap fix (control-substitution finding): reproduces
   DeviceCategorySelect.vue's own <style> block verbatim - a wide,
   non-scrolling grouped panel instead of the ~column-width default. */
.device-category .multiselect__content-wrapper {
  width: 360px !important;
}

/* Gap fix (invented-styling finding): EventDevice.vue's
   `::v-deep .suggested .multiselect` rule verbatim - a thick near-black
   border on the auto-suggested category, not Bootstrap's $warning orange. */
.suggested .multiselect {
  border: 3px solid #222 !important;
  width: calc(100% - 6px) !important;
}

/* EventDevice.vue's `.border-thick` (its own missing-category indicator -
   the category control turns this red border on validation failure). */
.border-thick {
  border: 3px solid red;
}

.multiselect__tags {
  cursor: pointer;
}

/* EventDevice.vue gave every field `class="mb-2"`; without it the next
   label (and its (i) icon) sits right against the field above. */
.device-form-card > .b-form-group {
  margin-bottom: 0.5rem;
}

/* The closed dropdowns, as develop's _global.scss styled vue-multiselect:
   `.multiselect .multiselect__tags { padding: 3px 40px 3px 12px; border: 1px
   solid #222 }`, the selected value with 5px left padding on a 25px line,
   DeviceCategorySelect.vue's 43px min-height, and the black chevron
   (`.multiselect__select:before`). The text sits directly in
   .multiselect__tags here, so the shared 0 bottom padding (balanced by
   vue-multiselect's inner span margin) left it sitting low. */
.device-form-card .multiselect__tags {
  position: relative;
  min-height: 43px;
  padding: 3px 40px 3px 17px;
  border: 1px solid #222;
  line-height: 25px;
  color: #000;
}

.device-form-card .multiselect__tags::after {
  content: '';
  position: absolute;
  top: 50%;
  right: 12px;
  margin-top: -4px;
  border-style: solid;
  border-width: 8px 8px 0;
  border-color: #000 transparent transparent;
}

.device-form-card .multiselect--active .multiselect__tags::after {
  transform: rotate(180deg);
}

.device-form-card .multiselect--disabled .multiselect__tags::after {
  display: none;
}

/* DeviceRepairStatus.vue: "Reduce the size of the options so they fit". */
[data-testid="device-form-card-repair"] .multiselect__tags,
[data-testid="device-form-card-repair"] .multiselect__content-wrapper {
  font-size: 14px;
}
</style>
