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
    <GroupLocationMap
        :lat.sync="currentLat"
        :lng.sync="currentLng"
        class="group-locationmap"
        ref="locationmap"
        v-if="showMap"
    />
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
import GroupLocationMap from './GroupLocationMap'
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
  components: {
    GroupLocationMap,
  },
  data () {
    return {
      currentValue: null,
      showMap: false,
      currentLat: null,
      currentLng: null,
      location: null,
      currentPostcode: null,
      timer: null,
    }
  },
  mounted() {
    this.currentValue = this.value
      this.currentLat = this.lat
      this.currentLng = this.lng
      this.showMap = this.lat || this.lng
    this.currentPostcode = this.postcode

      const token = document.getElementById('mapboxtoken')

      mapboxgl.accessToken = token.textContent;

      this.geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        types: 'country,region,place,postcode,locality,neighborhood',
        placeholder: this.$lang.get('groups.groups_location_placeholder')
      });

      // Tick to pick up id value.
      this.$nextTick(() => {
        this.geocoder.addTo('#' + this.inputid);
      })

      this.geocoder.on('result', (e) => {
        this.currentValue = e.result.place_name
        this.currentLat = e.result.center[1]
        this.currentLng = e.result.center[0]
        this.$emit('update:value', e.result.place_name)
        this.showMap = true
      });
    } catch (e) {
      console.error('Error setting up autocomplete',e)
    }
  },
  watch: {
    currentPostcode(newVal) {
      this.$emit('update:postcode', newVal)
    },
    currentLat(newVal) {
      this.$emit('update:lat', newVal)
    },
    currentLng(newVal) {
      this.$emit('update:lng', newVal)
    },
  },
  methods: {
    placeChanged(addressData, placeResultData) {
      this.currentValue = placeResultData.formatted_address
      this.$emit('update:value', this.currentValue)
      this.$emit('update:lat', addressData.latitude)
      this.$emit('update:lng', addressData.longitude)
    },
  }
}
</script>