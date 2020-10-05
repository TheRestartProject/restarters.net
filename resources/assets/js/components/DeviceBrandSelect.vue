<template>
  <div class="w-100 device-select-row">
    <multiselect
        :value="value"
        :placeholder="translatedBrand"
        :options="brands"
        track-by="id"
        label="brand_name"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:brand', $event.id)"
    />
    <div />
  </div>
</template>
<script>

export default {
  props: {
    value: {
      type: Number,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: true
    }
  },
  computed: {
    translatedBrand() {
      return this.$lang.get('devices.brand')
    }
  }
}
</script>
<style scoped lang="scss">
.device-select-row {
  display: grid;
  grid-template-columns: auto 50px;
}
</style>