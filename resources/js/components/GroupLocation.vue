<template>
  <div class="form-group">
    <label :for="$id('address-autocomplete')">{{ __('groups.location') }}:</label>
    <vue-google-autocomplete
        :id="$id('address-autocomplete')"
        name="location"
        classname="form-control"
        :placeholder="__('groups.groups_location_placeholder')"
        @placechanged="placeChanged"
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
    }
  },
  components: {
    VueGoogleAutocomplete
  },
  data () {
    return {
      currentValue: null,
      location: null,
      timer: null,
    }
  },
  computed: {
    groupLocation() {
      return this.group ? this.group.location : null
    },
    groupLat() {
      return this.group ? this.group.latitude : null
    },
    groupLng() {
      return this.group ? this.group.longitude : null
    },
  },
  mounted() {
    this.currentValue = this.value
    this.$refs.autocomplete.update(this.currentValue)
  },
  methods: {
    placeChanged(addressData, placeResultData) {
      this.currentValue = placeResultData.formatted_address
      this.$emit('update:value', this.currentValue)
      this.$emit('update:lat', addressData.latitude)
      this.$emit('update:lng', addressData.longitude)
    },
    useGroup() {
      this.$refs.autocomplete.update(this.groupLocation)
      this.$emit('update:lat', parseFloat(this.groupLat))
      this.$emit('update:lng', parseFloat(this.groupLng))
    },
  }
}
</script>