<template>
  <b-row>
    <b-col md="7">
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
        <b-btn variant="primary" size="sm" v-if="groupLocation && !online" @click="useGroup" class="mt-2" :disabled="currentValue === groupLocation">
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
import VueGoogleAutocomplete from 'vue-google-autocomplete'
import UniqueId from 'vue-unique-id';
import map from '../mixins/map'

Vue.use(UniqueId);

export default {
  mixins: [ map ],
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
    allGroups: {
      type: Array,
      required: false,
      default: null
    },
  },
  components: {
    VueGoogleAutocomplete
  },
  data () {
    return {
      currentValue: null,
      location: null,
      timer: null,
      online: false,
      idgroups: null,
      lat: null,
      lng: null
    }
  },
  computed: {
    group() {
      let ret = null
      if (this.idgroups) {
        this.groups.forEach(g => {
          if (parseInt(this.idgroups) === parseInt(g.idgroups)) {
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
  mounted() {
    this.currentValue = this.value
    this.$refs.autocomplete.update(this.currentValue)
    this.checkOtherInputs()
  },
  beforeDestroy () {
    clearTimeout(this.timer)
  },
  methods: {
    placeChanged(addressData, placeResultData) {
      this.currentValue = placeResultData.formatted_address
      this.lat = addressData.latitude
      this.lng = addressData.longitude

      // The formatted address returned can be slightly different from the value displayed.  Force them to be
      // the same so that we can disable the Use group button.
      this.$nextTick(() => {
        this.$refs.autocomplete.update(this.currentValue)
      })
    },
    useGroup() {
      this.$refs.autocomplete.update(this.groupLocation)
      this.lat = this.groupLat
      this.lng = this.groupLng
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