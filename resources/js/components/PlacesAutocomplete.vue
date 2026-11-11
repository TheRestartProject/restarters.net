<template>
  <div class="places-autocomplete-wrapper">
    <input
      :id="id"
      :name="name"
      :class="classname"
      :placeholder="placeholder"
      v-model="inputValue"
      @input="onInput"
      @blur="onBlur"
      autocomplete="off"
    />
    <ul v-if="suggestions.length > 0" class="places-autocomplete-suggestions list-group">
      <li
        v-for="suggestion in suggestions"
        :key="suggestion.place_id"
        class="list-group-item list-group-item-action"
        @mousedown.prevent="selectSuggestion(suggestion)"
      >
        {{ suggestion.description }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'PlacesAutocomplete',
  props: {
    id: { type: String, required: true },
    name: { type: String, default: '' },
    classname: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    types: { type: String, default: 'geocode' },
  },
  data() {
    return {
      inputValue: '',
      suggestions: [],
      debounceTimer: null,
    }
  },
  methods: {
    update(value) {
      // Set from outside, e.g. when the form is reset: a lookup queued for what was typed before is now stale.
      clearTimeout(this.debounceTimer)
      this.inputValue = value || ''
    },
    searchText() {
      return (this.inputValue || '').trim()
    },
    onInput() {
      this.$emit('change', this.inputValue)
      clearTimeout(this.debounceTimer)
      if (this.searchText().length < 3) {
        this.suggestions = []
        return
      }
      this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 300)
    },
    onBlur() {
      setTimeout(() => { this.suggestions = [] }, 200)
    },
    async fetchSuggestions() {
      // The server turns away a blank search (422).
      if (this.searchText().length < 3) {
        this.suggestions = []
        return
      }

      try {
        const resp = await fetch(
          `/maps/autocomplete?input=${encodeURIComponent(this.searchText())}&types=${encodeURIComponent(this.types)}`,
          { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
        )
        if (resp.ok) {
          const data = await resp.json()
          this.suggestions = data.predictions || []
        }
      } catch (e) {
        this.suggestions = []
      }
    },
    async selectSuggestion(suggestion) {
      this.inputValue = suggestion.description
      this.suggestions = []

      try {
        const resp = await fetch(
          `/maps/place-details?place_id=${encodeURIComponent(suggestion.place_id)}`,
          { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
        )
        if (!resp.ok) return

        const data = await resp.json()
        const result = data.result
        if (!result || !result.geometry) return

        const location = result.geometry.location
        const addressData = { latitude: location.lat, longitude: location.lng }

        if (result.address_components) {
          result.address_components.forEach(comp => {
            if (comp.types.includes('locality')) addressData.locality = comp.long_name
            if (comp.types.includes('country')) {
              addressData.country = comp.long_name
              addressData.country_code = comp.short_name
            }
            if (comp.types.includes('postal_code')) addressData.postal_code = comp.long_name
            if (comp.types.includes('administrative_area_level_1')) addressData.administrative_area_level_1_long = comp.long_name
          })
        }

        this.$emit('placechanged', addressData, { formatted_address: result.formatted_address })
      } catch (e) {
        // silently fail — user can retry
      }
    },
  }
}
</script>

<style scoped>
.places-autocomplete-wrapper {
  position: relative;
}
.places-autocomplete-suggestions {
  position: absolute;
  z-index: 1050;
  width: 100%;
  max-height: 250px;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0,0,0,.15);
}
</style>
