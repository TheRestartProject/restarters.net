<template>
  <b-row>
    <b-col md="7">
      <div class="form-group">
        <label :for="$id('address-autocomplete')">{{ __('events.field_event_venue') }}:</label>
        <div
            :id="inputid"
            name="location"
            aria-describedby="locationHelpBlock"
            ref="autocomplete"
            :class="{ hasError: hasError, 'p-0': true, 'm-0': true, 'form-control': true, 'group-location': true }"
        />
        <small id="locationHelpBlock">
          <span class="form-text text-danger" v-if="hasError">
            {{ __('events.address_error') }}
          </span>
          <span v-else>
          {{ __('events.field_venue_helper') }}
          </span>
        </small>
        <b-btn variant="primary" size="sm" v-if="groupLocation && !online" @click="useGroup" class="mt-2">
          {{ __('events.field_venue_use_group') }}
        </b-btn>
      </div>
    </b-col>
    <b-col lg="5">
      <l-map
          ref="map"
          :zoom="11"
          :center="[lat, lng]"
          :style="'width: 100%; height: 200px'"
          v-if="!online && lat !== null"
      >
        <l-tile-layer :url="tiles" :attribution="attribution" />
        <l-marker :lat-lng="[lat, lng]" :interactive="false" />
      </l-map>
    </b-col>
  </b-row>
</template>
<script>
import Vue from 'vue'
import UniqueId from 'vue-unique-id';
import map from '../mixins/map'
import mapboxgl from "mapbox-gl";
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';

Vue.use(UniqueId);

export default {
  mixins: [ map ],
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
    allGroups: {
      type: Array,
      required: false,
      default: null
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
  data () {
    return {
      currentValue: null,
      location: null,
      timer: null,
      inputid: null
    }
  },
  computed: {
    group() {
      let ret = null
      if (this.selectedGroup) {
        this.groups.forEach(g => {
          if (parseInt(this.selectedGroup) === parseInt(g.idgroups)) {
            ret = g
          }
        })
      }

      return ret
    },
    groupLocation() {
      return this.group ? this.group.location : null
    },
    groupLat() {
      return this.group ? this.group.latitude : null
    },
    groupLng() {
      return this.group ? this.group.longitude : null
    },
    groups() {
      return this.$store.getters['groups/list']
    },
  },
  created() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    let groups = {}

    this.allGroups.forEach(g => {
      groups[g.idgroups] = g
    })

    if (this.yourGroups) {
      this.yourGroups.forEach(g => {
        groups[g.idgroups].ingroup = true
      })
    }

    if (this.nearbyGroups) {
      this.yourGroups.forEach(g => {
        groups[g.idgroups].nearby = true
      })
    }

    this.$store.dispatch('groups/setList', {
      groups: Object.values(groups)
    })
  },
  watch: {
    value: {
      handler: function (val) {
        this.currentValue = val

        // Supposedly we can use setInput with a second parameter of false to avoid showing the autocomplete, but
        // that doesn't seem to work.  So we use this method from https://github.com/mapbox/mapbox-gl-geocoder/issues/401.
        this.geocoder._inputEl.value = this.currentValue
      },
      immediate: true,
    },
  },
  mounted() {
    this.currentValue = this.value

    try {
      this.inputid = this.$id('address-autocomplete')
      this.currentValue = this.value

      const token = document.getElementById('mapboxtoken')

      mapboxgl.accessToken = token.textContent;

      this.geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        placeholder: this.$lang.get('events.field_venue_placeholder'),
        proximity: 'ip'
      });

      // Tick to pick up id value.
      this.$nextTick(() => {
        this.geocoder.addTo('#' + this.inputid);
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
  beforeDestroy () {
    clearTimeout(this.timer)
  },
  methods: {
    useGroup() {
      this.geocoder._inputEl.value = this.groupLocation;
      this.$emit('update:value', this.groupLocation)
      this.$emit('update:lat', parseFloat(this.groupLat))
      this.$emit('update:lng', parseFloat(this.groupLng))
    },
    checkOtherInputs() {
      // This is a workaround until the whole form is converted to Vue.
      const online = document.getElementById('online')
      const idgroups = document.getElementById('event_group')

      if (online && idgroups) {
        this.online = online.checked
        this.idgroups = idgroups.value

        this.timer = setTimeout(this.checkOtherInputs, 200)
      } else {
        // This can happen as a timing window when you navigate away from the page and the DOM is destroyed.
        this.timer = null
      }
    }

  }
}
</script>
<style scoped lang="scss">
::v-deep(.mapboxgl-ctrl-geocoder) {
  width: 100% !important;
}
</style>