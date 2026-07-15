<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

// Location text input with Google Places autocomplete suggestions, plus a
// manual lat/lng fallback for callers that need coordinates directly.
// resources/js/components/GroupLocation.vue + PlacesAutocomplete.vue are
// the functional spec (design.md §6.2 B6 task brief).
//
// GroupController::createGroupv2/updateGroupv2 geocode the `location` text
// server-side (App\Helpers\Geocoder) - the only field the group create/edit
// forms actually need from this component is `location` (+ optional
// `postcode`). `lat`/`lng` are exposed as optional v-model props purely so
// this component is reusable by future callers (e.g. party create) that do
// need coordinates directly - pass `show-lat-lng` to reveal the manual
// fields.
//
// GET /api/v2/maps/autocomplete and .../place-details
// (api-contracts-phase-b.md B6) are documented but not implemented
// server-side yet on this branch - see docs/nuxt-migration/api-gaps.md B6.
// Suggestion fetches fail silently (no dropdown), leaving the plain text
// input as a fully-functional fallback: typing a location and submitting
// still works, because the server itself geocodes the text.
const props = defineProps({
  location: {
    type: String,
    default: '',
  },
  postcode: {
    type: String,
    default: '',
  },
  lat: {
    type: [Number, String],
    default: null,
  },
  lng: {
    type: [Number, String],
    default: null,
  },
  hasError: {
    type: Boolean,
    default: false,
  },
  canEditPostcode: {
    type: Boolean,
    default: true,
  },
  showLatLng: {
    type: Boolean,
    default: false,
  },
  showPostcode: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:location', 'update:postcode', 'update:lat', 'update:lng', 'place-selected'])

const { t } = useI18n()

const inputValue = ref(props.location || '')
const suggestions = ref([])
let debounceTimer = null

watch(
  () => props.location,
  (newVal) => {
    if (newVal !== inputValue.value) {
      inputValue.value = newVal || ''
    }
  },
)

function onInput() {
  emit('update:location', inputValue.value)
  // A manual edit invalidates any previously geocoded coordinates - the
  // server will re-geocode from the new text, but the client shouldn't go
  // on believing stale lat/lng are still valid for this text.
  emit('update:lat', null)
  emit('update:lng', null)

  clearTimeout(debounceTimer)
  if (!inputValue.value || inputValue.value.length < 3) {
    suggestions.value = []
    return
  }
  debounceTimer = setTimeout(fetchSuggestions, 300)
}

async function fetchSuggestions() {
  const { $api } = useNuxtApp()
  if (!$api) return

  try {
    const response = await $api.maps.autocomplete(inputValue.value)
    suggestions.value = response?.predictions || []
  } catch {
    // Gap (see comment above) - degrade to plain text entry.
    suggestions.value = []
  }
}

async function selectSuggestion(suggestion) {
  suggestions.value = []

  const { $api } = useNuxtApp()

  try {
    const response = await $api.maps.placeDetails(suggestion.place_id)
    const result = response?.result

    if (result?.formatted_address) {
      inputValue.value = result.formatted_address
      emit('update:location', result.formatted_address)
    }

    const geoLocation = result?.geometry?.location
    if (geoLocation) {
      emit('update:lat', geoLocation.lat)
      emit('update:lng', geoLocation.lng)
    }

    let postalCode = null
    for (const component of result?.address_components || []) {
      if (component.types.includes('postal_code')) {
        postalCode = component.long_name
      }
    }
    if (postalCode) {
      emit('update:postcode', postalCode)
    }

    emit('place-selected', result)
  } catch {
    // Gap - selecting a suggestion that can no longer be resolved just
    // leaves the typed text in place.
  }
}

function onBlur() {
  setTimeout(() => {
    suggestions.value = []
  }, 200)
}

function onPostcodeInput(event) {
  emit('update:postcode', event.target.value)
}

function onLatInput(event) {
  const value = event.target.value
  emit('update:lat', value === '' ? null : Number(value))
}

function onLngInput(event) {
  const value = event.target.value
  emit('update:lng', value === '' ? null : Number(value))
}
</script>

<template>
  <div class="location-picker">
    <BFormGroup :label="`${t('groups.location')}:`" label-for="location-picker-input">
      <div class="location-picker__autocomplete">
        <input
          id="location-picker-input"
          v-model="inputValue"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': hasError }"
          :placeholder="t('groups.groups_location_placeholder')"
          autocomplete="off"
          data-testid="location-picker-input"
          @input="onInput"
          @blur="onBlur"
        >
        <ul v-if="suggestions.length" class="location-picker__suggestions list-group" data-testid="location-picker-suggestions">
          <li
            v-for="suggestion in suggestions"
            :key="suggestion.place_id"
            class="list-group-item list-group-item-action"
            data-testid="location-picker-suggestion"
            @mousedown.prevent="selectSuggestion(suggestion)"
          >
            {{ suggestion.description }}
          </li>
        </ul>
      </div>
      <small v-if="hasError" class="form-text text-danger" data-testid="location-picker-error">
        {{ t('groups.geocode_failed') }}
      </small>
      <small v-else class="form-text text-muted">
        {{ t('groups.groups_location_small') }}
      </small>
    </BFormGroup>

    <BFormGroup v-if="showPostcode" :label="`${t('groups.postcode')}:`" label-for="location-picker-postcode">
      <input
        id="location-picker-postcode"
        type="text"
        class="form-control"
        :value="postcode"
        :readonly="!canEditPostcode"
        data-testid="location-picker-postcode"
        @input="onPostcodeInput"
      >
      <small class="form-text text-muted">{{ t('groups.groups_postcode_small') }}</small>
    </BFormGroup>

    <div v-if="showLatLng" class="row">
      <div class="col-6">
        <BFormGroup :label="`${t('client.groups.latitude_label')}:`" label-for="location-picker-lat">
          <input
            id="location-picker-lat"
            type="number"
            step="any"
            class="form-control"
            :value="lat"
            data-testid="location-picker-lat"
            @input="onLatInput"
          >
        </BFormGroup>
      </div>
      <div class="col-6">
        <BFormGroup :label="`${t('client.groups.longitude_label')}:`" label-for="location-picker-lng">
          <input
            id="location-picker-lng"
            type="number"
            step="any"
            class="form-control"
            :value="lng"
            data-testid="location-picker-lng"
            @input="onLngInput"
          >
        </BFormGroup>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.location-picker__autocomplete {
  position: relative;
}

.location-picker__suggestions {
  position: absolute;
  z-index: 1050;
  width: 100%;
  max-height: 250px;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
</style>
