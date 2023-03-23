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
      console.log('Mounted')
      this.inputid = this.$id('address-autocomplete')
      this.currentValue = this.value
      this.currentPostcode = this.postcode
      // this.$refs.autocomplete.update(this.currentValue)

      this.wait
      const token = document.getElementById('mapboxtoken')
      console.log('token', token)

      mapboxgl.accessToken = token.textContent;

      var geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        types: 'country,region,place,postcode,locality,neighborhood',
        placeholder: this.$lang.get('groups.groups_location_placeholder')
      });

      // Tick to pick up id value.
      this.$nextTick(() => {
        console.log('Add', '#' + this.inputid, document.getElementById(this.inputid).length)
        geocoder.addTo('#' + this.inputid);
        console.log('added')
      })

      geocoder.on('result', (e) => {
        this.currentValue = e.result.place_name
        this.$emit('update:value', e.result.place_name)
        this.$emit('update:lat', e.result.center[1])
        this.$emit('update:lng', e.result.center[0])
      });
    } catch (e) {
      console.error('mount',e)
    }
  },
  watch: {
    currentPostcode(newVal) {
      this.$emit('update:postcode', newVal)
    },
  },
  methods: {
    placeChanged(addressData, placeResultData) {
    },
  }
}
</script>
<style scoped lang="scss">
::v-deep(.mapboxgl-ctrl-geocoder) {
  width: 100% !important;
}
</style>