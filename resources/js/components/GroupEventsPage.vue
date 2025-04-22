<template>
  <div v-if="group">
    <AlertBanner />
    <GroupEvents
        heading-level="h1"
        heading-sub-level="h2"
        :idgroups="idgroups"
        :canedit="canedit"
        :calendar-copy-url="calendarCopyUrl"
        add-button
        :location="location"
    />
  </div>
</template>
<script>
import GroupEvents from './GroupEvents.vue'
import auth from '../mixins/auth'
import AlertBanner from './AlertBanner.vue'

export default {
  components: {
    GroupEvents, AlertBanner
  },
  mixins: [ auth ],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    initialGroup: {
      type: Object,
      required: true
    },
    events: {
      type: Array,
      required: true
    },
    calendarCopyUrl: {
      type: String,
      required: false,
      default: null
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    location: {
      type: String,
      required: true
    },
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    }
  },
  mounted () {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.events.forEach(e => {
      this.$store.dispatch('events/setStats', {
        idevents: e.idevents,
        stats: e.stats
      })
    })

    this.$store.dispatch('events/setList', {
      events: this.events
    })

    this.$store.dispatch('groups/set', this.initialGroup)
  }
}
</script>