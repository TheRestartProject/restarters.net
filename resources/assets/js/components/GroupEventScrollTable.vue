<template>
  <div>
    <b-table-simple sticky-header="50vh" responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
      <GroupEventsTableHeading />
      <b-tbody class="table-height">
        <GroupEventSummary v-for="e in toShow" :key="'event-' + e.idevents" :idevents="e.idevents" :canedit="canedit" :add-group-name="addGroupName" />
        <infinite-loading @infinite="loadMore" :force-use-infinite-wrapper="true">
          <span slot="no-results" />
          <span slot="no-more" />
          <span slot="spinner" />
        </infinite-loading>
      </b-tbody>
    </b-table-simple>
  </div>
</template>
<script>
import GroupEventSummary from './GroupEventSummary'
import GroupEventsTableHeading from './GroupEventsTableHeading'
import InfiniteLoading from 'vue-infinite-loading'

export default {
  components: {GroupEventsTableHeading, GroupEventSummary, InfiniteLoading},
  props: {
    events: {
      type: Array,
      required: true
    },
    canedit: {
      type: Boolean,
      required: true
    },
    addGroupName: {
      type: Boolean,
      required: true
    },
    limit: {
      type: Number,
      required: false,
      default: null
    },
  },
  data () {
    return {
      show: 0
    }
  },
  computed: {
    toShow() {
      return this.limit ? this.events.slice(0, this.limit) : this.events.slice(0, this.show)
    },
  },
  methods: {
    loadMore($state) {
      if (this.show < this.events.length) {
        this.show++
        $state.loaded()
      } else {
        $state.complete()
      }
    },
  }
}
</script>