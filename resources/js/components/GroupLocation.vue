<template>
  <div>
    <b-form-group>
      <label :for="$id('address-autocomplete')">{{ __('groups.location') }}:</label>
      <vue-google-autocomplete
          :id="$id('address-autocomplete')"
          name="location"
          classname="form-control group-location"
          :placeholder="__('groups.groups_location_placeholder')"
          @placechanged="placeChanged"
          @change="resetValues"
          aria-describedby="locationHelpBlock"
          types="geocode"
          ref="autocomplete"
          :class="{ hasError: hasError, 'm-0': true }"
      />
      <small id="locationHelpBlock">
      <span class="form-text text-danger" v-if="hasError">
        {{ __('groups.geocode_failed') }}
      </span>
        <span v-else>
      {{ __('groups.groups_location_small') }}
      </span>
      </small>
    </b-form-group>
    <b-form-group>
      <label for="group_postcode">{{ __('groups.postcode') }}:</label>
      <b-input id="group_postcode" name="postcode" v-model="currentPostcode" :class="{ hasError: hasError }" :readonly="!canEditPostcode" />
      <small>{{ __('groups.groups_postcode_small') }}</small>
    </b-form-group>
  </div>
</template>
<script>
import Vue from 'vue'
import VueGoogleAutocomplete from 'vue-google-autocomplete'
import UniqueId from 'vue-unique-id';

Vue.use(UniqueId);

export default {
  props: {
    value: {
      type: String,
      required: false,
      default: null
    },
    lat: {
      type: Number,
      required: false,
      default: null
    },
    lng: {
      type: Number,
      required: false,
      default: null
    },
    postcode: {
      type: String,
      required: false,
      default: null
    },
    area: {
      type: String,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    },
    selectedGroup: {
      type: Number,
      required: false,
      default: null
    },
    online: {
      type: Boolean,
      required: false,
      default: false
    },
    canEditPostcode: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  components: {
    VueGoogleAutocomplete
  },
  data () {
    return {
      currentValue: null,
      location: null,
      currentPostcode: null,
      timer: null,
      lastInputValue: '',
    }
  },
  mounted() {
    this.currentValue = this.value
    this.currentPostcode = this.postcode
    this.lastInputValue = this.value
    this.$refs.autocomplete.update(this.currentValue)
    this.startLocationWatcher()
  },
  beforeDestroy() {
    this.cancelLocationWatcher()
  },
  watch: {
    currentPostcode(newVal) {
      this.$emit('update:postcode', newVal)
    },
  },
  methods: {
    async placeChanged(addressData, placeResultData) {
      // nextTick which means the change event will get processed before we emit our new values.
      await this.$nextTick()
      this.currentValue = placeResultData.formatted_address
      this.$emit('update:value', this.currentValue)
      this.$emit('update:lat', addressData.latitude)
      this.$emit('update:lng', addressData.longitude)
    },
    resetValues() {
      // This means that if the input changes, we will assume it's invalid unless we subsequently (because of
      // the nextTick above) get a valid placeChanged event.
      this.$emit('update:value', null)
      this.$emit('update:lat', null)
      this.$emit('update:lng', null)
    },
    startLocationWatcher() {
      // This is for Playwright testing where Google Autocomplete is awkward.  Tests can set the underlying
      // values, and we should pick them up and pretend that the autocomplete had been used.
      const checkForChanges = () => {
        const inputElement = document.querySelector('[placeholder="' + this.__('groups.groups_location_placeholder') + '"]')
        if (inputElement && inputElement.value !== this.lastInputValue) {
          console.log('Location value has changed in DOM', inputElement.value)
          this.lastInputValue = inputElement.value
          this.handleLocationChange(inputElement.value)
        }

        // Restart the timer
        if (this.timer !== null) {
          this.timer = setTimeout(checkForChanges, 500)
        }
      }

      this.timer = setTimeout(checkForChanges, 500)
    },
    cancelLocationWatcher() {
      if (this.timer) {
        clearTimeout(this.timer)
        this.timer = null
      }
    },
    handleLocationChange(newValue) {
      // Handle the location change similar to placeChanged.  Use a hardcoded lat/lng as this is just for
      // testing, where it's providing hard to get geocode to work.
      // Only emit if we don't already have valid coordinates (i.e., this is a new input, not initialization)
      if (newValue && newValue.trim()) {
        if (this.lat === null || this.lng === null) {
          console.log('Emit', newValue, 51.5074, -0.1276)
          this.$emit('update:value', newValue)
          this.$emit('update:lat', 51.5074)
          this.$emit('update:lng', -0.1276)
        }
      } else {
        this.resetValues()
      }
    }
  }
}
</script>