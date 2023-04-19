<template>
  <div>
    <b-form-group>
      <label :for="$id('address-autocomplete')">{{ __('groups.location') }}:</label>
      <div
        :id="inputid"
        name="location"
        aria-describedby="locationHelpBlock"
        ref="autocomplete"
        :class="{ hasError: hasError, 'p-0': true, 'm-0': true, 'form-control': true, 'group-location': true }"
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
import UniqueId from 'vue-unique-id';
import mapboxgl from "mapbox-gl";
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';

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
  data () {
    return {
      currentValue: null,
      location: null,
      currentPostcode: null,
      timer: null,
      inputid: null
    }
  },
  mounted() {
    try {
      this.inputid = this.$id('address-autocomplete')
      this.currentValue = this.value
      this.currentPostcode = this.postcode

      const token = document.getElementById('mapboxtoken')

      mapboxgl.accessToken = token.textContent;

      this.geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        placeholder: this.$lang.get('groups.groups_location_placeholder'),
        proximity: 'ip'
      });

      // Tick to pick up id value.
      this.$nextTick(() => {
        this.geocoder.addTo('#' + this.inputid);

        // setInput always opens the suggestions even though the second parameter is supposed to stop it doing so.
        // So set the internal value.
        this.geocoder._inputEl.value = this.currentValue
      })

      this.geocoder.on('result', (e) => {
        this.currentValue = e.result.place_name
        this.$emit('update:value', e.result.place_name)
        this.$emit('update:lat', e.result.center[1])
        this.$emit('update:lng', e.result.center[0])
      });
    } catch (e) {
      console.error('Error setting up autocomplete',e)
    }
  },
  watch: {
    currentPostcode(newVal) {
      this.$emit('update:postcode', newVal)
    },
  },
}
</script>
<style scoped lang="scss">
::v-deep(.mapboxgl-ctrl-geocoder) {
  width: 100% !important;
}
</style>