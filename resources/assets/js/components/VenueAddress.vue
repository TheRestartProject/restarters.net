<template>
  <div>
    <div class="form-group">
      <label :for="$id('address-autocomplete')">{{ __('events.field_event_venue') }}:</label>
      <vue-google-autocomplete
          :id="$id('address-autocomplete')"
          name="location"
          classname="form-control"
          :placeholder="__('events.field_venue_placeholder')"
          v-on:placechanged="getAddressData"
          aria-describedby="locationHelpBlock"
          types="geocode"
          ref="autocomplete"
      >
      </vue-google-autocomplete>
      <p class="text-danger" v-if="error">{{ error }}</p>

      <small id="locationHelpBlock" class="form-text text-muted">
        {{ __('events.field_venue_helper') }}
      </small>
    </div>

    <input type="hidden" id="street_number" disabled="true" v-model="street_number">
    <input type="hidden" id="route" disabled="true" v-model="route">
    <input type="hidden" id="locality" disabled="true" v-model="locality">
    <input type="hidden" id="administrative_area_level_1" disabled="true" v-model="administrative_area_level_1">
    <input type="hidden" id="postal_code" disabled="true" v-model="postal_code">
    <input type="hidden" id="country" disabled="true" v-model="country">
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
      required: true
    },
    error: {
      type: String,
      required: false,
      default: null
    }
  },
  components: {
    VueGoogleAutocomplete
  },
  data () {
    return {
      location: null,
      street_number: null,
      route: null,
      locality: null,
      administrative_area_level_1: null,
      country: null,
      postal_code: null,
    }
  },
  mounted() {
    this.$refs.autocomplete.update(this.value)
  },
  methods: {
    getAddressData: function (addressData, placeResultData, id) {
      const comps = placeResultData.address_components

      comps.forEach(c => {
        switch (c.types[0]) {
          case 'street_number':
            this.street_number = c.short_name
            break
          case 'route':
            this.route = c.long_name
            break
          case 'locality':
            this.locality = c.long_name
            break
          case 'administrative_area_level_1':
            this.administrative_area_level_1 = c.short_name
            break
          case 'country':
            this.country = c.long_name
            break
          case 'postal_code':
            this.postal_code = c.short_name
            break
        }
      })
    }
  }
}
</script>