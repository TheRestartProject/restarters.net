<template>
  <div class="layout">
    <b-form-input
        v-model="searchName"
        type="search"
        :placeholder="__('events.search_name_placeholder')"
        class="mb-1 mb-md-0"
    />
  </div>
</template>
<script>
export default {
  props: {
  },
  data () {
    return {
      searchName: null,
    }
  },
  watch: {
    searchName(newVal) {
      this.$emit('update:name', newVal)
    },
  },
  computed: {
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto;

  @include media-breakpoint-up(md) {
    grid-column-gap: 20px;
    grid-template-columns: repeat( auto-fit, minmax(0px, 1fr) );
    grid-template-rows: 1fr;
  }
}
</style>