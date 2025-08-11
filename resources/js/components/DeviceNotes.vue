<template>
  <div class="w-100 device-select-row">
    <b-textarea rows="6" @change="$emit('update:notes', $event)" :placeholder="__('devices.placeholder_notes')" :value="notes" :disabled="disabled" />
    <div v-b-popover.html.left="__('devices.tooltip_notes')" class="ml-3 mt-2">
      <b-img class="icon clickable" :src="imageUrl('/icons/info_ico_black.svg')" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" :src="imageUrl('/icons/info_ico_green.svg')" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>
import images from '../mixins/images'

export default {
  mixins: [images],
  props: {
    notes: {
      type: String,
      required: false,
      default: null
    },
    iconVariant: {
      type: String,
      required: false,
      default: 'black'
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
  },
}
</script>