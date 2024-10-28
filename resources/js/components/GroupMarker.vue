<template>
  <div v-if="group">
    <l-marker :lat-lng="[lat, lng]" :interactive="false" :options="{
      title: group.name + ' - ' + __('groups.marker_title')
    }" @click="openModal" />
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
  },
  data() {
    return {
      showModal: false
    }
  },
  computed: {
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
