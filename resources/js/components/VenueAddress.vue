<template>
  <b-row>
    <b-col md="7">
      <div class="form-group">
        <label :for="$id('address-autocomplete')">{{ __('events.field_event_venue') }}:</label>
<!--        TODO Moves on focus-->
        <vue-google-autocomplete
            :id="$id('address-autocomplete')"
            name="location"
            classname="form-control"
            :placeholder="__('events.field_venue_placeholder')"
            @placechanged="placeChanged"
            @inputChange="clearLatLng"
            aria-describedby="locationHelpBlock"
            types="geocode"
            ref="autocomplete"
            :class="{ hasError: hasError }"
        />
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
<!--      TODO Map doesn't show on first load of event edit page.-->
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
      required: false,
      default: null
    },
    error: {
      type: String,
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
  components: {
    VueGoogleAutocomplete
  },
  data () {
    return {
      currentValue: null,
      location: null,
      timer: null,
      lat: null,
      lng: null,

      // When we create this component we may (when duplicating events) pass in lat/lng/value.  The setting of
      // the value will trigger a call to clearLatLng, and we don't want to emit null values and trample over the
      // ones we started with.
      suppressEmit: true
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
  mounted() {
    this.currentValue = this.value
    this.$refs.autocomplete.update(this.currentValue)
  },
  beforeDestroy () {
    clearTimeout(this.timer)
  },
  methods: {
    placeChanged(addressData, placeResultData) {
      this.currentValue = placeResultData.formatted_address

      // Ensure the next clearLatLng handler doesn't reset values.
      this.suppressEmit = true

      this.$nextTick(() => {
        // The formatted address returned can be slightly different from the value displayed.  Force them to be
        // the same so that we can disable the Use group button.
        this.$refs.autocomplete.update(this.currentValue)

        // Emit new lat/lng values.
        this.lat = addressData.latitude
        this.lng = addressData.longitude
        this.emit()
      })
    },
    clearLatLng() {
      // The input has changed.  Clear the lat/lng so that the parent knows we currently have an address which
      // is not geocodeable.  If we subsequently select a valid place from the drop-down list, then we'll call
      // placeChanged about and emit valid values.
      //
      // The suppressEmit jiggery-pokery is because both callbacks are made when we type and select a valid
      // address.
      if (!this.suppressEmit) {
        this.lat = null
        this.lng = null
        this.emit()
      }

      this.suppressEmit = false
    },
    emit() {
      this.$emit('update:value', this.currentValue)
      this.$emit('update:lat', this.lat)
      this.$emit('update:lng', this.lng)
    },
    useGroup() {
      this.$refs.autocomplete.update(this.groupLocation)
      this.lat = this.groupLat
      this.lng = this.groupLng
    }
  }
}
</script>