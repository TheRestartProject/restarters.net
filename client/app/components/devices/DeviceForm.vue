<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { suggestDeviceCategory } from '../../composables/useDeviceCategorySuggestion.js'
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
  estimate: props.device?.estimate ? String(props.device.estimate) : '',
  problem: props.device?.problem || '',
  notes: props.device?.notes || '',
  repairStatus: props.device?.repair_status || '',
  nextSteps: props.device?.next_steps || '',
  spareParts: props.device?.spare_parts || '',
  barrier: props.device?.barrier || '',
  quantity: 1,
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

// Powered devices only allow editing the weight for the "None of the
// above" misc category; unpowered devices always allow it
// (EventDevice.vue's `showWeight`/`weightRequired` computeds).
const showWeight = computed(() => !props.powered || form.category === miscCategoryId.value)
const weightRequired = computed(() => form.category === miscCategoryId.value)

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

function categoryOptionsForCluster(cluster) {
  return (cluster.categories || []).filter((c) => c.idcategories !== miscCategoryId.value)
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
function selectStatus(value) {
  form.repairStatus = value
  openDropdown.value = null
}

const nextStepsOptions = computed(() => (options.value.next_steps || []).map((n) => ({ value: n, label: t(nextStepLabel(n)) })))
const nextStepsSelectedLabel = computed(() => nextStepsOptions.value.find((o) => o.value === form.nextSteps)?.label || '')
function selectNextSteps(value) {
  form.nextSteps = value
  openDropdown.value = null
}

const sparePartsOptions = computed(() => (options.value.spare_parts || []).map((p) => ({ value: p, label: t(sparePartsLabel(p)) })))
const sparePartsSelectedLabel = computed(() => sparePartsOptions.value.find((o) => o.value === form.spareParts)?.label || '')
function selectSpareParts(value) {
  form.spareParts = value
  openDropdown.value = null
}

const barrierOptions = computed(() => (options.value.barriers || []).map((b) => ({ value: b.name, label: t(b.name) })))
const barrierSelectedLabel = computed(() => barrierOptions.value.find((o) => o.value === form.barrier)?.label || '')
function selectBarrier(value) {
  form.barrier = value
  openDropdown.value = null
}

// Item type/brand: a real <input> (so typed text is directly editable, as
// in vue-typeahead-bootstrap) plus a custom-styled suggestion panel below it
// instead of the browser's own <datalist> popup. maxMatches 5 matches
// DeviceType.vue/DeviceBrand.vue's own `:maxMatches="5"`.
const itemTypeMatches = computed(() => {
  const term = form.itemType.trim().toLowerCase()
  const list = term ? itemTypeSuggestions.value.filter((s) => s.toLowerCase().includes(term)) : itemTypeSuggestions.value
  return list.slice(0, 5)
})
function selectItemType(value) {
  form.itemType = value
  openDropdown.value = null
}

const brandNames = computed(() => brands.value.map((b) => b.brand_name))
const brandMatches = computed(() => {
  const term = form.brand.trim().toLowerCase()
  const list = term ? brandNames.value.filter((s) => s.toLowerCase().includes(term)) : brandNames.value
  return list.slice(0, 5)
})
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
    estimate: form.estimate ? parseFloat(form.estimate) : 0,
  }

  const optional = {
    item_type: form.itemType,
    brand: form.brand,
    model: form.model,
    problem: form.problem,
    notes: form.notes,
    repair_status: form.repairStatus,
    next_steps: showNextSteps.value ? form.nextSteps : '',
    spare_parts: showSpareParts.value ? form.spareParts : '',
    barrier: showBarrier.value ? form.barrier : '',
  }
  for (const [key, value] of Object.entries(optional)) {
    if (value) payload[key] = value
  }

  return payload
}

async function submit() {
  generalError.value = ''

  if (!form.category) {
    missingCategory.value = true
    return
  }
  missingCategory.value = false

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
            <FieldInfoPopover :content="itemTypeTooltip" :variant="infoIconVariant" />
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
            <FieldInfoPopover :content="t('devices.tooltip_category')" :variant="infoIconVariant" />
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
              id="device-form-brand"
              v-model="form.brand"
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
            <FieldInfoPopover :content="t('devices.tooltip_model')" :variant="infoIconVariant" />
          </template>
          <input
            id="device-form-model"
            v-model="form.model"
            type="text"
            class="form-control"
            :placeholder="t('devices.model_if_known')"
            :disabled="readonly"
            data-testid="device-form-model"
          >
        </BFormGroup>

        <BFormGroup :label="`${t('devices.age')}:`" label-for="device-form-age">
          <input
            id="device-form-age"
            v-model="form.age"
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

        <BFormGroup v-if="showWeight" :label="`${t('devices.weight')}:`" label-for="device-form-weight">
          <input
            id="device-form-weight"
            v-model="form.estimate"
            type="number"
            min="0"
            step="0.1"
            max="500"
            class="form-control"
            :disabled="readonly"
            data-testid="device-form-weight"
          >
          <small class="form-text text-muted">
            {{ weightRequired ? t('devices.required_impact') : t('devices.optional_impact') }}
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
            <FieldInfoPopover :content="t('devices.tooltip_problem')" :variant="infoIconVariant" />
          </template>
          <textarea
            id="device-form-problem"
            v-model="form.problem"
            rows="4"
            class="form-control"
            :disabled="readonly"
            data-testid="device-form-problem"
          />
        </BFormGroup>

        <BFormGroup label-for="device-form-notes">
          <template #label>
            {{ t('client.devices.notes') }}:
            <FieldInfoPopover :content="t('devices.tooltip_notes')" :variant="infoIconVariant" />
          </template>
          <textarea
            id="device-form-notes"
            v-model="form.notes"
            rows="4"
            class="form-control"
            :placeholder="t('devices.placeholder_notes')"
            :disabled="readonly"
            data-testid="device-form-notes"
          />
        </BFormGroup>
      </div>
    </div>

    <BFormGroup v-if="!editing" :label="`${t('client.devices.quantity')}:`" label-for="device-form-quantity">
      <select id="device-form-quantity" v-model.number="form.quantity" class="form-select w-auto" data-testid="device-form-quantity">
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
</style>
