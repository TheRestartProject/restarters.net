<template>
  <div v-if="group">
    <l-marker
        :lat-lng="[lat, lng]" :interactive="false" :options="{
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
      hovering: false
    }
  },
  watch: {
    hover(val) {
      this.hovering = val
    }
  },
  computed: {
    icon() {
      let icon = "/images/vendor/leaflet/dist/marker-icon.png"

      if (this.hovering) {
        icon = "/images/vendor/leaflet/dist/marker-icon-red.png"
      } else if (this.highlight) {
        icon = "/images/vendor/leaflet/dist/marker-icon-green.png"
      }

      return L.icon({
        iconUrl: icon,
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
