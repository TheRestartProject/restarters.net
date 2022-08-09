<template>
  <div>
    <div v-if="loaded">
      <GroupEventScrollTable :events="events" :canedit="true" :addGroupName="true"  v-if="events.length" />
      <p v-else class="pt-3 pb-3">__('events.moderation_none').</p>
    </div>
    <div v-else class="vue-placeholder-large" />
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
  computed: {
    events() {
      return Object.values(this.$store.getters['events/getModerate'])
    },
  },
  async mounted() {
    await this.$store.dispatch('events/getModerationRequired')
    this.$nextTick(() => {
      this.loaded = true
    })
  }
}
</script>