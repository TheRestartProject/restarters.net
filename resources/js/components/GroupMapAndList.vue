<template>
  <div>
    Hover: {{ hover }}
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
      :groupids="groupidsInBounds"
      class="mt-3"
      count
      v-if="groupidsInBounds.length"
      your-area="yourArea"
      :your-groups="yourGroups"
      :hover.sync="hover"
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
    yourGroups: {
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
    }
  },
  mounted() {
    this.$store.dispatch('groups/list', {
      details: true
    })
  },
  methods: {
    groupsChanged(groupids) {
      this.groupidsInBounds = groupids
    },
  },
}
</script>
