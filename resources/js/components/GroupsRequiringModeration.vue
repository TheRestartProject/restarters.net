<template>
  <div>
    <GroupsTable :groups="groups" approve />
  </div>
</template>
<script>
import GroupsTable from './GroupsTable'

export default {
  props: {
    groups: {
      type: Array,
      required: true
    }
  },
  components: {
    GroupsTable,
  },
  mounted() {
    this.$store.dispatch('groups/setList', {
      events: this.groups
    })
  }
}
</script>