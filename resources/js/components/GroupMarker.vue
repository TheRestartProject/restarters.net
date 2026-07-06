<template>
  <div v-if="group">
    <!-- Markers must stay interactive: click opens the info modal and
         mouseover drives the highlight. -->
    <l-marker
        :lat-lng="[lat, lng]" :options="{
          title: group.name + ' - ' + __('groups.marker_title'),
        }" :icon="icon"
        @click="openModal"
        @mouseover="hovering = true"
        @mouseout="hovering = false"
    />
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
        className: className,
      })
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
      this.showModal = true
    }
  }
}
</script>
<style scoped lang="scss">
</style>
