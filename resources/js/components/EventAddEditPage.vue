<template>
  <div>
    <h1>
      <span v-if="!currentid">{{ __('events.add_new_event') }}</span>
      <span v-html="editTitle" />
    </h1>
    <b-card no-body class="box mt-4">
      <b-card-body class="p-4">
        <EventAddEdit :duplicate-from="currentdup" :idevents="currentid" :groups="groups" :csrf="csrf"
                      :create-group="createGroup"
                      @created="eventCreated" @edited="justCreated = false" :just-created="justCreated"
                      :can-approve="canApprove" :can-network="canNetwork"
                      :key="bump" />
      </b-card-body>
    </b-card>
  </div>
</template>
<script>
import EventAddEdit from './EventAddEdit.vue'
import auth from '../mixins/auth'
import event from '../mixins/event'

export default {
  components: {EventAddEdit},
  mixins: [auth, event],
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
    createGroup: {
      type: Number,
      required: false,
      default: null
    }
  },
  data() {
    return {
      currentid: null,
      currentdup: null,
      bump: 1,
      event: null,
      justCreated: false
    }
  },
  computed: {
    editTitle() {
      if (!this.currentid) {
        return null
      }

      if (!this.event) {
        return null
      }

      let title = this.event.title ? this.event.title : this.event.location

      // Escape it
      const div = document.createElement('div')
      div.innerText = title
      title = div.innerHTML

      let ret =  this.$lang.get('events.editing', {
        event: '<a style="color:black; text-decoration:underline" href="/party/view/' + this.currentid  +'">' + title + '</a>'
      })

      return ret
    }
  },
  created() {
    // We have a data prop for this so that we can switch to the edit view after creation.
    this.currentid = this.idevents
    this.currentdup = this.duplicateFrom
  },
  methods: {
    async eventCreated(id) {
      // Get the new event into store - we need it for the title on this page.
      this.event = await this.$store.dispatch('events/fetch', {
        id
      })

      this.currentid = id
      this.currentdup = null
      this.justCreated = true

      // We want to change the URL.  If we had a full SPA we could do this with Vue Router, but we don't, so modify
      // the history state directly.
      // Get host and path from current URL
      try {
        // Get just the host from the current URL
        let host = window.location.host
        let protocol = window.location.protocol

        window.history.replaceState({}, "", protocol + "//" + host + "/party/edit/" + id)
      } catch (e) {
        console.log('Failed to update path')
      }

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