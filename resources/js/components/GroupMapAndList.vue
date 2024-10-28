<template>
  <div>
    <GroupMap
        v-model:ready="mapready"
        :initial-bounds="initialBounds"
        :min-zoom="minZoom"
        :max-zoom="maxZoom"
        :bounds.sync="bounds"
        :network="network"
        @groups="groupsChanged($event)"
    />
    <GroupsTable
      :groupids="groupidsInBounds"
      class="mt-3"
      count
      v-if="groupidsInBounds.length"
      your-area="yourArea"
    />
  </div>
</template>
<script>
import {MAX_MAP_ZOOM, MIN_MAP_ZOOM} from "../constants";
import GroupMap from "./GroupMap";
import GroupsTable from "./GroupsTable.vue";

export default {
  components: {
    GroupsTable,
    GroupMap,
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
    fetchGroups: {
      type: Boolean,
      required: false,
      default: true,
    },
  },
  data() {
    return {
      infiniteId: +new Date(),
      groupidsInBounds: [],
      mapready: false,
      bounds: null,
    }
  },
  mounted() {
    if (this.fetchGroups) {
      this.$store.dispatch('groups/list', {
        details: true
      })
    } else {
      // The list of groups will be fetched within GroupMap, so no need to fetch it here.
    }
  },
  methods: {
    groupsChanged(groupids) {
      this.groupidsInBounds = groupids
    },
  },
}
</script>
