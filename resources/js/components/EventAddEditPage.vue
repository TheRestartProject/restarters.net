<template>
  <div>
    <h1>{{ __('events.add_new_event') }}</h1>
    <b-card no-body class="box">
      <b-card-body class="p-4">
        <b-form action="/party/create" method="post">
          <input type="hidden" name="_token" :value="CSRF" />
          <div class="layout">
            <div class="d-flex flex-wrap event-name">
              <b-form-group class="flex-grow-1 pr-4">
                <label for="event_name" class="moveright">{{ __('events.field_event_name') }}:</label>
                <b-input type="text" id="event_name" name="venue" :placeholder="__('events.field_event_name_helper')" />
              </b-form-group>
              <div class="align-self-center online d-flex">
                <label for="online">{{ __('events.online_event_question') }}</label>
                <b-form-checkbox id="online" name="online" class="ml-2" v-model="eventOnline" />
              </div>
            </div>
            <div class="event-group">
              <b-form-group>
                <label for="event_group" class="moveright">{{ __('events.field_event_group') }}:</label>
                <multiselect
                    v-model="groupValue"
                    :options="groupOptions"
                    track-by="idgroups"
                    label="name"
                    :allow-empty="false"
                    deselectLabel=""
                    :placeholder="__('partials.please_choose')"
                />
                <input type="hidden" name="group" :value="idgroups" />
              </b-form-group>
            </div>
            <div class="form-group event-description">
              <b-form-group>
                <label for="event_desc" class="moveright">{{ __('events.field_event_desc') }}:</label>
                <RichTextEditor name="free_text" class="moveright" />
              </b-form-group>
            </div>
            <div class="event-date">
              <label for="event_date">{{ __('events.field_event_date') }}:</label>
              <EventDatePicker :date.sync="eventDate" class="p-0" />
            </div>
            <b-form-group class="event-time">
              <label>{{ __('events.field_event_time') }}:</label>
              <EventTimeRangePicker class="movedown" :start.sync="eventStart" :end.sync="eventEnd" />
            </b-form-group>
            <div class="event-address">
              <VenueAddress :all-groups="groups" :value.sync="address" :selected-group="idgroups" :online="eventOnline" />
            </div>
            <div class="event-create button-group row">
              <div class="offset-lg-3 col-lg-7 d-flex align-items-right justify-content-end text-right">
                {{ __('events.before_submit_text') }}
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-end">
                <b-btn variant="primary" @click="create" class="break" type="submit">
                  {{ __('events.create_event')}}
                </b-btn>
              </div>
            </div>
          </div>
        </b-form>
      </b-card-body>
    </b-card>
  </div>
</template>
<script>
import event from '../mixins/event'
import auth from '../mixins/auth'
import RichTextEditor from './RichTextEditor'
import EventDatePicker from './EventDatePicker'
import EventTimeRangePicker from './EventTimeRangePicker'
import VenueAddress from './VenueAddress'

// TODO Set initial values for duplicate event
// TODO Actually create using AJAX
// TODO Vuelidate

export default {
  components: {VenueAddress, EventTimeRangePicker, EventDatePicker, RichTextEditor},
  mixins: [ event, auth ],
  props: {
    initialEvent: {
      type: Object,
      required: false,
      default: null
    },
    idevents: {
      type: Number,
      required: false,
      default: null
    },
  },
  data () {
    return {
      groupValue: null,
      eventDate: null,
      eventStart: null,
      eventEnd: null,
      eventOnline: false,
      address: null
    }
  },
  computed: {
    groups() {
      return this.$store.getters['groups/list']
    },
    groupOptions() {
      return this.groups ? this.groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
    CSRF() {
      return this.$store.getters['auth/CSRF']
    },
    idgroups() {
      return this.groupValue ? this.groupValue.idgroups : null
    }
  },
  mounted() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.initialEvent.idevents = this.idevents
    this.$store.dispatch('events/set', this.initialEvent)

    this.$store.dispatch('groups/setList', {
      groups: Object.values(groups)
    })
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

.layout {
  display: grid;
  grid-column-gap: 40px;

  grid-template-columns: 1fr;

  @include media-breakpoint-up(lg) {
    grid-template-columns: 2fr 1fr 1fr;
  }

  .event-name {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .event-group {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
  }

  .event-description {
    grid-row: 3 / 4;
    grid-column: 1 / 2;
  }

  .event-date {
    grid-row: 4 / 5;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 1 / 2;
      grid-column: 2 / 3;
    }
  }

  .event-time {
    grid-row: 5 / 6;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 2 / 3;
      grid-column: 2 / 3;
    }
  }

  .event-address {
    grid-row: 6 / 7;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 3 / 4;
      grid-column: 2 / 4;
    }
  }

  .event-create {
    grid-row: 7 / 8;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 4 / 5;
      grid-column: 1 / 4;
    }
  }
}

.online {
  min-width: 50px;
  margin-top: 1rem;

  label {
    font-weight: normal;
  }
}

/deep/ .form-control, /deep/ .custom-checkbox input {
  border: 2px solid $black !important;
}

/deep/ .multiselect {
  border: 1px solid $black !important;
  font-family: "Open Sans", "sans-serif" !important;

  outline: 3px;
  margin: 2px;
  margin-right: 3px;
  width: calc(100% - 4px) !important;

  &.multiselect--active {
    border: 3px solid $black !important;
    outline: 0px !important;
    margin: 0px !important;
  }
}

/deep/ .ql-toolbar {
  border-top: 2px solid $black !important;
  border-left: 2px solid $black !important;
  border-right: 2px solid $black !important;
}

/deep/ .ql-container {
  border-bottom: 2px solid $black !important;
  border-left: 2px solid $black !important;
  border-right: 2px solid $black !important;
}

/deep/ .ql-container.ql-snow {
  border: unset;
}

.moveright {
  margin-left: 2px;
}

.movedown {
  margin-top: 2px;
}
</style>