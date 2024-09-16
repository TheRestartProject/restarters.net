<template>
  <div>
    <GroupMap
        v-model:ready="mapready"
        :initial-bounds="initialBounds"
        :min-zoom="minZoom"
        :max-zoom="maxZoom"
        :bounds.sync="bounds"
        @groups="groupsChanged($event)"
    />
    <GroupsTable
      :groups="groupsInBounds"
      class="mt-3"
      count
      v-if="groupsInBounds.length"
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
  },
  data() {
    return {
      infiniteId: +new Date(),
      groupidsInBounds: [],
      mapready: false,
      bounds: null,
    }
  },
  computed: {
    closestGroups() {
      // TODO
      return []
    },
    groupsInBounds() {
      return this.$store.getters['groups/list'].filter(group => this.groupidsInBounds.includes(group.id))
    },
  },
  mounted() {
    // The list of groups will be fetched within GroupMap, so no need to fetch it here.
  },
  watch: {
    groupidsInBounds(newVal) {
      // TODO Fetch all the groups.  This will be slow - we may need a better API.
      console.log('Group ids in bounds changed', newVal)
      newVal.forEach(groupid => {
        if (!this.$store.getters['groups/get'](groupid)) {
          console.log('Fetch', groupid)
          this.$store.dispatch('groups/fetch', {
            id: groupid
          })
        }
      })
    },
  },
  methods: {
    groupsChanged(groupids) {
      this.groupidsInBounds = groupids
    },
  },
}
</script>
