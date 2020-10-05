<template>
  <div class="d-flex w-100 justify-content-between">
    <b-input @change="$emit('update:model', $event)" :placeholder="translatedModel" size="lg" />
    <div v-b-popover.html.left :title="translatedTooltipModel" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

export default {
  props: {
    model: {
      type: String,
      required: false,
      default: null
    },
    iconVariant: {
      type: String,
      required: false,
      default: 'black'
    }
  },
  data () {
    return {
      value: null
    }
  },
  computed: {
    translatedModel() {
      return this.$lang.get('devices.model')
    },
    translatedTooltipModel() {
      return this.$lang.get('devices.tooltip_model')
    }
  }
}
</script>
