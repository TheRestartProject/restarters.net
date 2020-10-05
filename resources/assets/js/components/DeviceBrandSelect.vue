<template>
  <div class="d-flex w-100 justify-content-between">
    <multiselect
        v-model="brand"
        :placeholder="translatedBrand"
        :options="brands"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="brand_name"
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:brand', $event)"
    />
    <div class="ml-3 mt-2 spacer" />
  </div>
</template>
<script>

export default {
  props: {
    brand: {
      type: Object,
      required: false,
      default: function() {
        return []
      }
    },
    brands: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      value: null,
    }
  },
  computed: {
    translatedBrand() {
      return this.$lang.get('devices.brand')
    }
  }
}
</script>
<style scoped>
.spacer {
  width: 28px;
}
</style>