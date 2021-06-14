<template>
  <div>
    <div class="form-group">
      <label :for="$id('address-autocomplete')">{{ __('events.field_event_venue') }}:</label>
      <vue-google-autocomplete
          :id="$id('address-autocomplete')"
          name="location"
          classname="form-control"
          :placeholder="__('events.field_venue_placeholder')"
          @placechanged="placeChanged"
          aria-describedby="locationHelpBlock"
          types="geocode"
          ref="autocomplete"
      >
      </vue-google-autocomplete>
      <p class="text-danger" v-if="error">{{ error }}</p>

      <small id="locationHelpBlock" class="form-text text-muted">
        {{ __('events.field_venue_helper') }}
      </small>
      <b-btn variant="link" size="sm" v-if="groupLocation" @click="useGroup" class="pl-0" :disabled="currentValue === groupLocation">
        {{ __('events.field_venue_use_group') }}
      </b-btn>
    </div>
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
    },
    groupLocation: {
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
      currentValue: null,
      location: null,
    }
  },
  mounted() {
    this.currentValue = this.value
    this.$refs.autocomplete.update(this.currentValue)
  },
  methods: {
    placeChanged(addressData, placeResultData) {
      this.currentValue = placeResultData.formatted_address

      // The formatted address returned can be slightly different from the value displayed.  Force them to be
      // the same so that we can disable the Use group button.
      this.$nextTick(() => {
        this.$refs.autocomplete.update(this.currentValue)
      })
    },
    useGroup() {
      this.$refs.autocomplete.update(this.groupLocation)
    }
  }
}
</script>