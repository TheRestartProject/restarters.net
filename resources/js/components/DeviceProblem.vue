<template>
  <div class="w-100 device-select-row">
    <b-textarea rows="6" @change="$emit('update:problem', $event)" :placeholder="__('devices.devices_description')" :value="problem" :disabled="disabled" />
    <div v-b-popover.html.left="__('devices.tooltip_problem')" class="ml-3 mt-2">
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
    problem: {
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