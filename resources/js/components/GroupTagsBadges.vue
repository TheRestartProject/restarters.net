<template>
  <span v-if="tags && tags.length" class="group-tags-badges">
    <b-badge
        v-for="tag in tags"
        :key="tag.id"
        :title="tag.description"
        variant="info"
        pill
        class="mr-1"
    >{{ tag.name }}</b-badge>
  </span>
</template>
<script>
export default {
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
    tags() {
      return this.group && this.group.tags ? this.group.tags : []
    },
  },
}
</script>
<style scoped lang="scss">
.group-tags-badges {
  display: inline;
}
</style>
