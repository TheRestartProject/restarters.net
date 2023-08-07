<template>
  <div class="d-flex flex-wrap event-name">
    <b-form-group class="flex-grow-1 pr-4">
      <label for="event_name" class="moveright">{{ __('events.field_event_name') }}:</label>
      <b-input type="text" id="event_name" name="venue" :placeholder="__('events.field_event_name_helper')" v-model="currentVenue" :class="{ hasError: hasError, 'pl-3': true }" />
    </b-form-group>
    <div class="align-self-end mb-3 online d-flex">
      <label for="online">{{ __('events.online_event_question') }}</label>
      <b-form-checkbox id="online" name="online" class="ml-2" v-model="currentOnline" />
    </div>
  </div>
</template>
<script>
export default {
  props: {
    venue: {
      type: String,
      required: false,
      default: null
    },
    online: {
      type: Boolean,
      required: false,
      default: false
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentVenue: null,
      currentOnline: false
    }
  },
  watch: {
    venue: {
      handler: function(newVal) {
        this.currentVenue = newVal
      },
      immediate: true
    },
    online: {
      handler: function(newVal) {
        this.currentOnline = newVal
      },
      immediate: true
    },
    currentVenue(newVal) {
      this.$emit('update:venue', newVal)
    },
    currentOnline(newVal) {
      this.$emit('update:online', newVal)
    }
  }
}
</script>
<style scoped lang="scss">
input {
  margin: 2px !important;

  &:focus {
    margin: 2px !important;
  }
}
</style>