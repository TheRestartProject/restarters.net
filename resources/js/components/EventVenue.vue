<template>
  <div class="d-flex flex-wrap event-name">
    <b-form-group class="flex-grow-1 pr-4">
      <label for="event_name" class="moveright">{{ __('events.field_event_name') }}:</label>
<!--      TODO Moves on focus-->
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
  mounted() {
    this.currentVenue = this.venue
    this.currentOnline = this.online
  },
  watch: {
    currentVenue(newVal) {
      this.$emit('update:venue', newVal)
    },
    currentOnline(newVal) {
      this.$emit('update:online', newVal)
    }
  }
}
</script>