<template>
  <b-badge
      :title="title" v-if="archived_at" variant="secondary" pill class="nounderline">{{ __('groups.archived_group') }}
  </b-badge>
</template>
<script>
import group from '../mixins/group'

export default {
  mixins: [group],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    archived_at() {
      return this.group && this.group.archived_at
    },
    title() {
      return this.__('groups.archived_group_title', {
        date: this.archived_at
      })
    },
  },
}
</script>