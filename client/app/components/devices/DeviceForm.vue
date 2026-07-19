<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { suggestDeviceCategory } from '../../composables/useDeviceCategorySuggestion.js'
import DevicePhotos from './DevicePhotos.vue'

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
// Deliberate simplifications vs the legacy form (same design decision
// EventForm.vue/GroupForm.vue already made - "vue-multiselect isn't
// installed for this migration", design.md §2): every legacy
// vue-multiselect/vue-typeahead-bootstrap field (item type, category,
// brand, repair status, next steps, spare parts, barrier) becomes a plain
// native <select>/<input list=datalist> here - natively keyboard-operable,
// no extra dependency.
//
// Delete is deliberately NOT a button on this form: DeviceRow.vue (the
// summary row this form replaces while editing) already owns its own
// delete icon + confirm, matching EventDeviceSummary.vue's inline
// <EventDevice ... /> usage, which never passes `deleteButton` either.
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
})

const emit = defineEmits(['saved', 'cancel'])

const { t } = useI18n()
const devicesStore = useDevicesStore()

const editing = computed(() => !!props.device)
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

        <BFormGroup :label="`${t('devices.item_type')}:`" label-for="device-form-item-type">
          <input
            id="device-form-item-type"
            v-model="form.itemType"
            type="text"
            class="form-control"
            list="device-form-item-type-list"
            data-testid="device-form-item-type"
          >
          <datalist id="device-form-item-type-list">
            <option v-for="s in itemTypeSuggestions" :key="s" :value="s" />
          </datalist>
          <small v-if="itemTypeUnknown" class="form-text text-warning d-block" data-testid="device-form-item-type-warning">
            {{ t('devices.unknown_item_type') }}
          </small>
        </BFormGroup>

        <BFormGroup :label="`${t('devices.category')}:`" label-for="device-form-category">
          <select
            id="device-form-category"
            v-model.number="form.category"
            class="form-select"
            :class="{ 'is-invalid': missingCategory, 'border-warning': categorySuggested }"
            data-testid="device-form-category"
          >
            <option :value="null" />
            <optgroup v-for="cluster in clusters" :key="cluster.id" :label="t(cluster.name)">
              <option v-for="c in categoryOptionsForCluster(cluster)" :key="c.idcategories" :value="c.idcategories">
                {{ t(c.name) }}
              </option>
            </optgroup>
            <option :value="miscCategoryId">{{ t('partials.category_none') }}</option>
          </select>
          <div v-if="missingCategory" class="invalid-feedback d-block" data-testid="device-form-category-error">
            {{ t('events.form_error') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('devices.brand')}:`" label-for="device-form-brand">
          <input
            id="device-form-brand"
            v-model="form.brand"
            type="text"
            class="form-control"
            list="device-form-brand-list"
            data-testid="device-form-brand"
          >
          <datalist id="device-form-brand-list">
            <option v-for="b in brands" :key="b.id" :value="b.brand_name" />
          </datalist>
          <small v-if="brandUnknown" class="form-text text-warning d-block" data-testid="device-form-brand-warning">
            {{ t('devices.unknown_brand') }}
          </small>
        </BFormGroup>

        <BFormGroup :label="`${t('devices.model')}:`" label-for="device-form-model">
          <input id="device-form-model" v-model="form.model" type="text" class="form-control" data-testid="device-form-model">
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
            data-testid="device-form-weight"
          >
          <small class="form-text text-muted">
            {{ weightRequired ? t('devices.required_impact') : t('devices.optional_impact') }}
          </small>
        </BFormGroup>

        <DevicePhotos v-if="editing" :event-id="eventId" :device-id="device.id" :images="device.images || []" />
      </div>

      <div class="device-form-card" data-testid="device-form-card-repair">
        <h3>{{ t('devices.title_repair') }}</h3>

        <BFormGroup :label="`${t('devices.repair_status')}:`" label-for="device-form-status">
          <select id="device-form-status" v-model="form.repairStatus" class="form-select" data-testid="device-form-status">
            <option value="" />
            <option :value="STATUS_FIXED">{{ t('partials.fixed') }}</option>
            <option :value="STATUS_REPAIRABLE">{{ t('partials.repairable') }}</option>
            <option :value="STATUS_END_OF_LIFE">{{ t('partials.end_of_life') }}</option>
          </select>
        </BFormGroup>

        <BFormGroup v-if="showNextSteps" :label="`${t('devices.repair_details')}:`" label-for="device-form-next-steps">
          <select id="device-form-next-steps" v-model="form.nextSteps" class="form-select" data-testid="device-form-next-steps">
            <option value="" />
            <option v-for="n in options.next_steps" :key="n" :value="n">{{ t(nextStepLabel(n)) }}</option>
          </select>
        </BFormGroup>

        <BFormGroup v-if="showSpareParts" :label="`${t('devices.spare_parts')}:`" label-for="device-form-spare-parts">
          <select id="device-form-spare-parts" v-model="form.spareParts" class="form-select" data-testid="device-form-spare-parts">
            <option value="" />
            <option v-for="p in options.spare_parts" :key="p" :value="p">{{ t(sparePartsLabel(p)) }}</option>
          </select>
        </BFormGroup>

        <BFormGroup v-if="showBarrier" :label="`${t('partials.choose_barriers')}:`" label-for="device-form-barrier">
          <select id="device-form-barrier" v-model="form.barrier" class="form-select" data-testid="device-form-barrier">
            <option value="" />
            <option v-for="b in options.barriers" :key="b.id" :value="b.name">{{ t(b.name) }}</option>
          </select>
        </BFormGroup>
      </div>

      <div class="device-form-card" data-testid="device-form-card-assessment">
        <h3>{{ t('devices.title_assessment') }}</h3>

        <BFormGroup :label="`${t('devices.devices_description')}:`" label-for="device-form-problem">
          <textarea id="device-form-problem" v-model="form.problem" rows="4" class="form-control" data-testid="device-form-problem" />
        </BFormGroup>

        <BFormGroup :label="`${t('client.devices.notes')}:`" label-for="device-form-notes">
          <textarea
            id="device-form-notes"
            v-model="form.notes"
            rows="4"
            class="form-control"
            :placeholder="t('devices.placeholder_notes')"
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

    <div class="d-flex gap-2 justify-content-center mt-3">
      <BButton type="submit" variant="primary" :disabled="submitting" data-testid="device-form-submit">
        {{ editing ? t('partials.save') : t('partials.add_device') }}
      </BButton>
      <BButton variant="tertiary" data-testid="device-form-cancel" @click="cancel">
        {{ t('partials.cancel') }}
      </BButton>
    </div>
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
</style>
