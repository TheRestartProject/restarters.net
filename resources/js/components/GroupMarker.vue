<template>
  <div v-if="group">
    <!-- Markers must stay interactive: click opens the info modal and
         mouseover drives the highlight. -->
    <l-marker
        :lat-lng="[lat, lng]" :options="{
          alt: label,
        }" :icon="icon"
        @click="openModal"
        @mouseover="markerHover(true)"
        @mouseout="markerHover(false)"
    >
      <!-- A Leaflet tooltip, not the native `title` attribute: `title` is shown
           by the browser on its own ~1s delay, which we can't tune. This appears
           as soon as the pointer reaches the pin. -->
      <l-tooltip :options="{ direction: 'top', opacity: 0.95 }">{{ label }}</l-tooltip>
    </l-marker>
    <GroupInfoModal v-if="showModal" ref="modal" :id="group.id" @close="showModal = false "/>
  </div>
</template>
<script>
import map from '../mixins/map'
import GroupInfoModal from "./GroupInfoModal.vue";

export default {
  components: {GroupInfoModal},
  mixins: [map],
  props: {
    id: {
      type: Number,
      required: true,
    },
    highlight: {
      type: Boolean,
      required: false,
      default: false,
    },
    hover: {
      type: Boolean,
      required: false,
      default: false,
    },
    // [lat, lng] override for where to draw the pin. GroupMap separates
    // groups at the exact same coordinates by a tiny display-only nudge
    // (see its clusterPoints); the marker must draw at that nudged position
    // rather than the store's, or both pins still stack on the same spot.
    latLng: {
      type: Array,
      required: false,
      default: null,
    }
  },
  data() {
    return {
      showModal: false,
      // Seed from the prop: the watcher below only fires on change, so a
      // marker created while its row is already hovered must start red.
      hovering: this.hover
    }
  },
  watch: {
    hover(val) {
      this.hovering = val
    }
  },
  computed: {
    icon() {
      // The pin shares the cluster bubble's restart style - black border,
      // white inner (user feedback; it was the stock blue Leaflet teardrop).
      // An inline-SVG divIcon rather than an image, so the follow/hover
      // states recolour the fill directly instead of hue-rotating a PNG.
      let className = 'group-pin'

      if (this.hovering) {
        className += ' group-pin--hover'
      } else if (this.highlight) {
        className += ' group-pin--yours'
      }

      return L.divIcon({
        html:
          '<svg viewBox="0 0 30 42" width="30" height="42" aria-hidden="true">' +
          '<path d="M15 1.5C7.8 1.5 2 7.3 2 14.5c0 9.5 13 26 13 26s13-16.5 13-26C28 7.3 22.2 1.5 15 1.5z"/>' +
          '<circle cx="15" cy="14.5" r="4.5"/>' +
          '</svg>',
        // Without iconSize/iconAnchor Leaflet pins the icon's top-left corner
        // to the coordinate, so the teardrop would hang down and to the right
        // and appear to point at somewhere else entirely: anchor the tip.
        iconSize: [30, 42],
        iconAnchor: [15, 42],
        // Measured from the anchor (the tip), so the tooltip clears the pin head.
        tooltipAnchor: [0, -42],
        className: className,
      })
    },
    label() {
      return this.group.name + ' - ' + this.__('groups.marker_title')
    },
    group() {
      return this.$store.getters['groups/get'](this.id)
    },
    lat() {
      if (this.latLng) {
        return this.latLng[0]
      }
      return this.group.location ? this.group.location.lat : this.group.lat
    },
    lng() {
      if (this.latLng) {
        return this.latLng[1]
      }
      return this.group.location ? this.group.location.lng : this.group.lng
    }
  },
  methods: {
    openModal() {
      // The store may only hold the lean index entry for this group; fetch
      // the full row so the modal can show location and next event.
      this.$store.dispatch('groups/hydrate', { ids: [this.id] })
      this.showModal = true
    },
    markerHover(over) {
      this.hovering = over
      // Tell the list so the matching row highlights (the reverse of the
      // row-hover → red pin direction).
      this.$emit('update:hover', over ? this.id : null)
    }
  }
}
</script>
<style scoped lang="scss">
</style>
