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
      // Only the stock blue marker ships in public/images; recolour it with a
      // CSS hue-rotate class rather than referencing images we don't have.
      let className = ''

      if (this.hovering) {
        className = 'group-marker-hover'
      } else if (this.highlight) {
        className = 'group-marker-yours'
      }

      return L.icon({
        iconUrl: '/images/vendor/leaflet/dist/marker-icon.png',
        // Without iconSize/iconAnchor Leaflet pins the image's top-left corner to
        // the coordinate, so the whole 25x41 teardrop hangs down and to the right
        // and the pin appears to point at somewhere else entirely. These are
        // Leaflet's own defaults for this image: anchor the tip of the pin.
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        // Measured from the anchor (the tip), so the tooltip clears the pin head.
        tooltipAnchor: [0, -41],
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
      return this.group.location ? this.group.location.lat : this.group.lat
    },
    lng() {
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
