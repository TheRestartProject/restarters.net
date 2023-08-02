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
            :class="{ hasError: hasError, 'm-0': true }"
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
        this.$refs.autocomplete.update(this.currentValue)
      },
      immediate: true
    },
  },
  beforeDestroy () {
    clearTimeout(this.timer)
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