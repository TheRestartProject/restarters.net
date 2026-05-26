<template>
  <div>
    <div class="loader d-flex justify-content-around" v-if="loading">
      <div class="d-flex flex-column justify-content-around">
        <v-icon name="sync" scale=4 class="fa-spin" />
      </div>
    </div>
    <div v-else>
      <GroupMap
          v-model:ready="mapready"
          :initial-bounds="initialBounds"
          :min-zoom="minZoom"
          :max-zoom="maxZoom"
          :bounds.sync="bounds"
          :network="network"
          :your-groups="yourGroups"
          :hover="hover"
          @groups="groupsChanged($event)"
      />
      <GroupsTable
          :groupids="effectiveGroupIds"
          class="mt-3"
          count
          your-area="yourArea"
          :your-groups="yourGroups"
          :hover.sync="hover"
          :search="showFilters"
          :all-group-tags="availableTags"
          :show-tags="canManageTags"
      />
    </div>
  </div>
</template>
<script>
import {MAX_MAP_ZOOM, MIN_MAP_ZOOM} from "../constants";
import GroupMap from "./GroupMap.vue";
import GroupsTable from "./GroupsTable.vue";
import VIcon from 'vue-awesome/components/Icon'

export default {
  components: {
    GroupsTable,
    GroupMap,
    VIcon,
  },
  props: {
    initialBounds: {
      type: Array,
      required: true,
    },
    minZoom: {
      type: Number,
      required: false,
      default: MIN_MAP_ZOOM,
    },
    maxZoom: {
      type: Number,
      required: false,
      default: MAX_MAP_ZOOM,
    },
    network: {
      type: Number,
      required: false,
      default: null,
    },
    yourGroups: {
      type: Array,
      required: false,
      default: () => [],
    },
    showFilters: {
      type: Boolean,
      required: false,
      default: false,
    },
    canManageTags: {
      type: Boolean,
      required: false,
      default: false,
    },
    availableTags: {
      type: Array,
      required: false,
      default: () => [],
    },
  },
  data() {
    return {
      infiniteId: +new Date(),
      groupidsInBounds: [],
      mapready: false,
      bounds: null,
      hover: null,
      loading: true
    }
  },
  computed: {
    effectiveGroupIds() {
      if (this.groupidsInBounds.length) {
        return this.groupidsInBounds
      }
      const allGroups = this.$store.getters['groups/list']
      const filtered = this.network
        ? allGroups.filter(g => g.networks && g.networks.some(n => n.id === this.network))
        : allGroups
      return filtered.map(g => g.id || g.idgroups)
    },
  },
  async mounted() {
    // Wrap to avoid an unhandled async rejection breaking Vue 2's
    // scheduler — see notes in GroupsRequiringModeration.vue.
    try {
      await this.$store.dispatch('groups/list', {
        details: true
      })
    } catch (e) {
      console.error('Failed to fetch groups list:', e)
    }

    this.loading = false
  },
  methods: {
    groupsChanged(groupids) {
      this.groupidsInBounds = groupids
    },
  },
}
</script>
<style scoped lang="scss">
.loader {
  height: 400px;
  width: 100%;
  opacity: .1;
  pointer-events: none;
  color: lightgrey;
}
</style>
