<template>
  <CollapsibleSection class="lineheight" collapsed :count="upcoming.length" count-badge>
    <template slot="title">
      <div class="d-flex">
        {{ translatedTitle }}
      </div>
    </template>
    <template slot="content">
      <b-tabs class="ourtabs w-100">
        <b-tab active title-item-class="w-50" class="pt-2">
          <template slot="title">
            <div class="d-flex justify-content-between">
              <div>
                <b>{{ translatedUpcoming }}</b> ({{ upcoming.length }})
              </div>
            </div>
          </template>
          <p v-if="!upcoming.length">
            {{ translatedNoUpcoming }}.
          </p>
          <b-table-simple v-else responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
            <GroupEventsTableHeading />
            <b-tbody class="borders">
              <GroupEventSummary v-for="e in upcomingFirst" :key="'event-' + e.idevents" :idevents="e.idevents" />
            </b-tbody>
          </b-table-simple>
          TODO Calendar, add, see all
        </b-tab>
        <b-tab title-item-class="w-50" class="pt-2">
          <template slot="title">
            <div class="d-flex justify-content-between">
              <div>
                <b>{{ translatedPast }}</b> ({{ past.length }})
              </div>
            </div>
          </template>
          <p v-if="!past.length">
            {{ translatedNoPast }}.
          </p>
          Past event list
        </b-tab>
      </b-tabs>
    </template>
  </CollapsibleSection>
</template>
<script>
import group from '../mixins/group'
import CollapsibleSection from './CollapsibleSection'
import GroupEventsTableHeading from './GroupEventsTableHeading'
import moment from 'moment'
import GroupEventSummary from './GroupEventSummary'

export default {
  components: {GroupEventSummary, CollapsibleSection, GroupEventsTableHeading},
  mixins: [ group ],
  props: {
    events: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('groups.group_events')
    },
    translatedUpcoming() {
      return this.$lang.get('groups.upcoming_active')
    },
    translatedNoUpcoming() {
      return this.$lang.get('groups.no_upcoming_events')
    },
    translatedNoPastEvents() {
      return this.$lang.get('groups.no_past_events')
    },
    translatedPast() {
      return this.$lang.get('groups.past')
    },
    past() {
      return this.events.filter(e => {
          const start = new moment(e.event_date + ' ' + e.start)
          return start.isBefore()
      })
    },
    pastFirst() {
      return this.past.slice(0, 3)
    },
    upcoming() {
      console.log(this.events)
      return this.events.filter(e => {
        const start = new moment(e.event_date + ' ' + e.start)
        return start.isAfter()
      })
    },
    upcomingFirst() {
      return this.upcoming.slice(0, 3)
    }
  },
  created() {
    // The events are passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    this.$store.dispatch('events/setList', {
      events: this.events
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.lineheight {
  line-height: 2;
}

.readmore {
  white-space: pre-wrap !important;
}

.icon {
  width: 20px;
  margin-bottom: 3px;
}

.lower {
  text-transform: lowercase;
}
</style>