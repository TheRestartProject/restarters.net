<template>
  <div>
    <div v-if="loaded">
      <GroupsTable :groups="groups" approve v-if="groups.length" />
      <p v-else class="pt-3 pb-3">__('groups.moderation_none').</p>
    </div>
    <div v-else class="vue-placeholder-large" />
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
  components: {
    GroupsTable,
  },
  computed: {
    groups() {
      return Object.values(this.$store.getters['groups/getModerate'])
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