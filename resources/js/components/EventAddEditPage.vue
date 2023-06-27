<template>
  <div>
    <h1>{{ __('events.add_new_event') }}</h1>
    <b-card no-body class="box mt-4">
      <b-card-body class="p-4">
        <EventAddEdit :duplicate-from="duplicateFrom" :idevents="currentid" :groups="groups" :csrf="csrf"
                      @created="eventCreated" @edited="justCreated = false" :just-created="justCreated"
                      :can-approve="canApprove" :can-network="canNetwork"
                      :key="bump" />
      </b-card-body>
    </b-card>
  </div>
</template>
<script>
import EventAddEdit from './EventAddEdit'
import auth from '../mixins/auth'

export default {
  components: {EventAddEdit},
  mixins: [auth],
  props: {
    duplicateFrom: {
      type: Number,
      required: false,
      default: null
    },
    idevents: {
      type: Number,
      required: false,
      default: null
    },
    groups: {
      type: Array,
      required: true
    },
    canApprove: {
      type: Boolean,
      required: false,
      default: false
    },
    canNetwork: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data() {
    return {
      currentid: null,
      bump: 1,
      justCreated: false
    }
  },
  created() {
    // We have a data prop for this so that we can switch to the edit view after creation.
    this.currentid = this.idevents
  },
  methods: {
    eventCreated(id) {
      this.currentid = id
      this.justCreated = true
      this.bump++
    }
  }
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