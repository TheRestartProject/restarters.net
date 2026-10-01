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
          :your-area="yourArea"
          :your-lat="yourLat"
          :your-lng="yourLng"
          :hover="hover"
          :groupids="matchingGroupIds"
          :frame-request="frameRequest"
          @update:hover="hover = $event"
          @update:centre="centre = $event"
          @searched="searchedPoint = $event"
          @groups="groupsChanged($event)"
      />
      <GroupsTable
          :groupids="effectiveGroupIds"
          class="mt-3"
          count
          :hover.sync="hover"
          :centre="centre"
          :reference-point="referencePoint"
          :search="showFilters"
          @update:filters="filtersChanged"
          :all-group-tags="availableTags"
          :show-tags="canManageTags"
      />
    </div>
  </div>
</template>
<script>
import {MAX_MAP_ZOOM, MIN_MAP_ZOOM} from "../constants";
import GroupMap from "./GroupMap.vue";
import { matchesFilters, inNetwork } from '../misc/groupFilter'

// Long enough to sit through normal typing, short enough that the map follows
// promptly once you stop.
const REFRAME_DEBOUNCE_MS = 500
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
    yourArea: {
      type: String,
      required: false,
      default: '',
    },
    yourLat: {
      type: Number,
      required: false,
      default: null,
    },
    yourLng: {
      type: Number,
      required: false,
      default: null,
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
      // null = the map hasn't told us what's in view yet; an empty array is a
      // real answer (nothing in view) and must not fall back to all groups.
      groupidsInBounds: null,
      // Where the map is centred, which orders the list by what's nearest.
      centre: null,
      // Where the last place search landed. While set, the list's distance
      // column anchors here rather than to the user's own location.
      searchedPoint: null,
      // What the user has typed or picked in the filter bar. Applied to every
      // group, not just the ones currently in view, so that filtering moves the
      // map rather than only shortening the list under it.
      filters: null,
      // Bumped when a filter changes, to ask the map to frame the matches.
      frameRequest: 0,
      frameTimer: null,
      mapready: false,
      bounds: null,
      hover: null,
      loading: true
    }
  },
  computed: {
    scopedGroups() {
      return this.$store.getters['groups/list'].filter(g => inNetwork(g, this.network))
    },
    matchingGroupIds() {
      // Everything the filter allows, wherever it is - this is what the map
      // draws, so a search can take you to a group you can't currently see.
      return this.scopedGroups
        .filter(g => matchesFilters(g, this.filters))
        .map(g => g.id || g.idgroups)
    },
    effectiveGroupIds() {
      if (this.groupidsInBounds === null) {
        return this.matchingGroupIds
      }

      // What's both in view and allowed by the filter.
      const matching = this.matchingGroupIds
      return this.groupidsInBounds.filter(id => matching.includes(id))
    },
    referencePoint() {
      // What the list's distance column measures from: the searched place if
      // there has been a search, else the user's own location, else nothing
      // (the column hides).
      if (this.searchedPoint) {
        return this.searchedPoint
      }

      if (this.yourLat !== null && this.yourLng !== null && !isNaN(+this.yourLat) && !isNaN(+this.yourLng)) {
        return { lat: +this.yourLat, lng: +this.yourLng }
      }

      return null
    },
  },
  beforeDestroy() {
    // Don't wake up and touch a component that has gone away.
    if (this.frameTimer) {
      clearTimeout(this.frameTimer)
      this.frameTimer = null
    }
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
    filtersChanged(filters) {
      // The list and the pins follow immediately - they just get shorter. Moving
      // the viewport waits until the typing stops, or the map lurches around
      // under the user on every keystroke.
      this.filters = filters

      if (this.frameTimer) {
        clearTimeout(this.frameTimer)
      }

      this.frameTimer = setTimeout(() => {
        this.frameTimer = null
        this.frameRequest++
      }, REFRAME_DEBOUNCE_MS)
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
