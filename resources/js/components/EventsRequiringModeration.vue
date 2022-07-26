<template>
  <div>
    <GroupEventScrollTable :events="events" :canedit="true" :addGroupName="true" />
  </div>
</template>
<script>
import GroupEventScrollTable from './GroupEventScrollTable'

export default {
  props: {
    events: {
      type: Array,
      required: true
    }
  },
  components: {
    GroupEventScrollTable,
  },
  mounted() {
    this.$store.dispatch('events/setList', {
      events: this.events
    })
  }
}
</script>