<template>
  <b-modal
      id="addData"
      v-model="showModal"
      no-stacking
      no-body
      size="md"
      title-class="w-100 p-0"
      header-class="p-0"
      body-class="p-0"
      title-tag="h2"
  >
    <template slot="modal-title">
      <div class="d-flex">
        {{ __('devices.add_data_title') }}
        <b-img src="/images/fixometer_doodle.svg" class="ml-4" />
      </div>
      <hr class="hr-dashed mb-25 mt-10">
    </template>
    <div>
      {{ __('devices.add_data_description') }}
    </div>
    <div>
      <label for="items_cat" class="mt-2">{{ __('devices.add_data_group') }}:</label>
      <b-select v-model="groupId" :options="groupOptions" id="group_member" />
      <b-alert v-if="groupId && !events.length && !fetching" variant="warning" class="mt-2" show>
        {{ __('groups.create_group_first') }}
      </b-alert>
      <div v-else>
        <label for="items_cat" class="mt-2">{{ __('devices.add_data_event') }}:</label>
        <b-select v-model="eventId" :options="eventOptions" id="events" />
      </div>
    </div>
    <template slot="modal-footer">
      <b-button v-if="groupId && !events.length && !fetching" variant="primary" size="sm" @click="createEvent" :disabled="!groupId">
        {{ __('events.create_event') }}
      </b-button>
      <b-button v-else variant="primary" size="sm" @click="gotoEvent">
        {{ __('devices.add_data_action_button') }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import moment from 'moment'

export default {
  data: function() {
    return {
      showModal: false,
      groupId: null,
      eventId: null,
      fetching: false,
    }
  },
  computed: {
    groups() {
      let groups = this.$store.getters['groups/list']

      return groups ? groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
    events() {
      const events = Object.values(this.$store.getters['events/getAll'])
      return events.sort((a,b) => new moment(b.start).unix() - new moment(a.start).unix())
    },
    groupOptions() {
      return this.groups.map(g => {
        return {
          value: g.idgroups,
          text: g.name
        }
      })
    },
    eventOptions() {
      return this.events.map(e => {
        return {
          value: e.id,
          text: new moment(e.start).format('DD MMM YY') + ' / ' + e.title
        }
      })
    },
  },
  watch: {
    groupId: {
      immediate: true,
      handler: async function (newVal) {
        if (newVal) {
          this.fetching = true

          await this.$store.dispatch('events/clear')

          await this.$store.dispatch('events/fetchByGroup', {
            id: newVal
          })

          this.fetching = false
        }
      }
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    gotoEvent() {
      if (this.eventId) {
        window.location = '/party/view/' + this.eventId + '#devices-section'
      }
    },
    createEvent() {
      window.location = '/party/create/' + this.groupId
    },
  }
}
</script>