<template>
  <div>
    <h1>{{ __('events.add_new_event') }}</h1>
    <b-card no-body class="box mt-4">
      <b-card-body class="p-4">
        <EventAddEdit :duplicate-from="duplicateFrom" :initial-event="initialEvent" :groups="groups" />
      </b-card-body>
    </b-card>
  </div>
</template>
<script>
import EventAddEdit from './EventAddEdit'

// TODO Native inputs for date/time

export default {
  components: {EventAddEdit},
  props: {
    duplicateFrom: {
      type: Object,
      required: false,
      default: null
    },
    initialEvent: {
      type: Object,
      required: false,
      default: null
    },
    groups: {
      type: Array,
      required: true
    }
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.box {
  background-color: $white;
  box-shadow: 5px 5px $black;
  border: 1px solid $black;
  border-radius: 0;
}
</style>