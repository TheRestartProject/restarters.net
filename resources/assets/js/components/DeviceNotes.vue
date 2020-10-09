<template>
  <div class="w-100 device-select-row">
    <b-textarea rows="6" @change="$emit('update:notes', $event)" :placeholder="translatedNotes" :value="notes" />
    <div v-b-popover.html.left="translatedTooltipNotes" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

export default {
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
    }
  },
  computed: {
    translatedNotes() {
      return this.$lang.get('devices.placeholder_notes')
    },
    translatedTooltipNotes() {
      return this.$lang.get('devices.tooltip_notes')
    }
  }
}
</script>