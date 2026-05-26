<template>
  <div>
    <div v-if="loaded && events.length">
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
import GroupEventScrollTable from './GroupEventScrollTable.vue'
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
    networks: {
      type: Array,
      required: false,
      default: null
    }
  },
  computed: {
    events() {
      let ret = Object.values(this.$store.getters['events/getModerate'])

      if (this.networks) {
        // We are trying to show only data for specific networks.
        ret = ret.filter((e) =>  {
          var intersection = e.group.networks.filter(x => this.networks.includes(x.id));

          if (intersection.length) {
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
    try {
      await this.$store.dispatch('events/getModerationRequired')
    } catch (e) {
      // See note in GroupsRequiringModeration.vue: an unhandled async
      // rejection here breaks Vue 2's nextTick scheduler.
      console.error('Failed to fetch events requiring moderation:', e)
    }
    this.$nextTick(() => {
      this.loaded = true
    })
  }
}
</script>