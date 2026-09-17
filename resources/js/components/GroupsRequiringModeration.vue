<template>
  <div v-if="loaded && groups.length">
    <section class="table-section" id="groups-1">
      <GroupsTable :groupids="groupIds" approve />
    </section>
  </div>
</template>
<script>
import GroupsTable from './GroupsTable.vue'
import auth from '../mixins/auth'

export default {
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
  components: {
    GroupsTable,
  },
  computed: {
    groups() {
      let ret = Object.values(this.$store.getters['groups/getModerate'])

      if (this.networks) {
        // We are trying to show only data for specific networks.
        ret = ret.filter((e) =>  {
          var intersection = e.networks.filter(x => this.networks.includes(x.id));

          if (intersection.length) {
            return true
          } else {
            return false
          }
        })
      }

      return ret
    },
    groupIds() {
      // GroupsTable's `groupids` prop is the ids it should render — the
      // moderate store uses { idgroups, id } (via newToOld), so accept either.
      return this.groups.map(g => g.idgroups || g.id)
    },
  },
  async mounted() {
    try {
      await this.$store.dispatch('groups/getModerationRequired')
    } catch (e) {
      // Don't let a server-side failure here become an unhandled async
      // promise rejection — Vue 2's scheduler can leak the `pending` flag
      // when a lifecycle hook rejects unobserved, after which subsequent
      // dep.notify() calls queue watchers that never get flushed.
      console.error('Failed to fetch groups requiring moderation:', e)
    }
    this.$nextTick(() => {
      this.loaded = true
    })
  }
}
</script>