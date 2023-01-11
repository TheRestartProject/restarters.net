<template>
  <b-form-group>
    <label for="event_link">{{ __('events.field_event_link') }}:</label>
    <b-input type="url" id="event_link" name="link" :placeholder="__('events.field_event_link_helper')"
             v-model="currentLink" :class="{ hasError: hasError }"/>
  </b-form-group>
</template>
<script>
export default {
  props: {
    link: {
      type: String,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentLink: null,
    }
  },
  mounted () {
    this.currentLink = this.link
  },
  watch: {
    currentLink(newVal) {
      this.$emit('update:link', newVal)
    },
  }
}
</script>