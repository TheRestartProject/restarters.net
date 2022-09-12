<template>
  <div v-if="loaded && groups.length">
    <h2 class="mt-4">{{ __('groups.groups_title_admin') }}</h2>
    <section class="table-section" id="groups-1">
      <GroupsTable :groups="groups" approve />
    </section>
  </div>
</template>
<script>
import GroupsTable from './GroupsTable'
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
  },
  async mounted() {
    await this.$store.dispatch('groups/getModerationRequired')
    this.$nextTick(() => {
      this.loaded = true
    })
  }
}
</script>