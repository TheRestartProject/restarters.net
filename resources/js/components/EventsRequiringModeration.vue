<template>
  <div>
    <div v-if="loaded && events.length">
      <h2 class="mt-4">{{ __('events.events_title_admin') }}</h2>
      <section class="mt-40">
        <GroupEventScrollTable
            :events="events"
            :canedit="true"
            :addGroupName="true"
            sort-by="date_long"
            :sort-desc="false" />
      </section>
    </div>
  </div>
</template>
<script>
import GroupEventScrollTable from './GroupEventScrollTable'
import auth from '../mixins/auth'

export default {
  components: {
    GroupEventScrollTable,
  },
  mixins: [auth],
  data () {
    return {
      loaded: false
    }
  },
  props: {
    network: {
      type: Number,
      required: false,
      default: null
    }
  },
  computed: {
    events() {
      let ret = Object.values(this.$store.getters['events/getModerate'])

      if (this.network) {
        // We are trying to show only data for a specific network.
        ret = ret.filter((e) =>  {

          console.log("Filter", e, this.network)
          if (e.group.networks.find((n) => {
            return n.id === this.network
          })) {
            return true
          } else {
            return false
          }
        })
      }

      return ret
    },
  },
  async mounted() {
    console.log("Mounted")
    await this.$store.dispatch('events/getModerationRequired')
    console.log("Got events")
    this.$nextTick(() => {
      console.log("Set loaded")
      this.loaded = true
    })
  }
}
</script>