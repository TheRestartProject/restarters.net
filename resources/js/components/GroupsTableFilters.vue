<template>
  <div class="gt-layout">
    <b-form-input
        v-model="searchName"
        type="search"
        :placeholder="__('groups.search_name_placeholder')"
        class="mb-1 mb-md-0"
    />
    <multiselect
        v-if="showTags"
        v-model="searchTags"
        :placeholder="__('groups.search_tags_placeholder')"
        :options="allGroupTags"
        track-by="id"
        label="tag_name"
        :multiple="true"
        :allow-empty="false"
        deselect-label=""
        :taggable="true"
        selectLabel=""
        class="m-0 mb-1 mb-md-0"
        allow-empty
        :selectedLabel="__('partials.remove')"
        open-direction="bottom"
    />
  </div>
</template>
<script>
export default {
  props: {
    allGroupTags: {
      type: Array,
      required: false,
      default: null
    },
    showTags: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      searchName: null,
      searchTags: null
    }
  },
  watch: {
    searchName(newVal) {
      this.$emit('update:name', newVal)
    },
    searchTags(newVal) {
      this.$emit('update:tags', newVal)
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.gt-layout {
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
