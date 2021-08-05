<template>
  <div>
    <h1>{{ __('events.add_new_event') }}</h1>
    <b-card no-body class="box mt-4">
      <b-card-body class="p-4">
        <b-form action="/party/create" method="post">
          <input type="hidden" name="_token" :value="CSRF"/>

          <div class="layout">
            <EventVenue
                class="flex-grow-1 pr-4 event-venue"
                :venue.sync="eventVenue"
                :online.sync="eventOnline"
                :has-error="$v.eventVenue.$error"
                ref="eventVenue"/>
            <EventGroup
                class="event-group"
                :value.sync="idgroups"
                :v="this.$v.idgroups"
                ref="eventGroup"
            />
            <div class="form-group event-description">
              <b-form-group>
                <label for="event_desc">{{ __('events.field_event_desc') }}:</label>
                <RichTextEditor
                    id="event_desc"
                    name="free_text"
                    class="moveright"
                    :value.sync="free_text"
                    :has-error="$v.free_text.$error"
                    ref="free_text"/>
              </b-form-group>
            </div>
            <div class="event-date">
              <label for="event_date">{{ __('events.field_event_date') }}:</label>
              <EventDatePicker
                  id="event_date"
                  class="p-0"
                  :date.sync="eventDate"
                  :has-error="$v.eventDate.$error"
                  ref="eventDate"/>
            </div>
            <b-form-group class="event-time mt-3 mt-lg-0">
              <label for="event_time">{{ __('events.field_event_time') }}:</label>
              <EventTimeRangePicker
                  id="event_time"
                  class="movedown"
                  :start.sync="eventStart"
                  :end.sync="eventEnd"
                  :has-error="$v.eventStart.$error || $v.eventEnd.$error"
                  ref="eventStart"/>
            </b-form-group>
            <!-- TODO The address component is indented slightly, and shouldn't be.-->
            <!-- TODO Error message about choosing something and the next level up if required -->
            <VenueAddress
                :all-groups="allGroups"
                :value.sync="eventAddress"
                :lat.sync="lat"
                :lng.sync="lng"
                :selected-group="idgroups"
                :online="eventOnline"
                class="event-address"
                :has-error="$v.eventAddress.$error"
                ref="eventAddress"
            />
            <div class="event-create button-group row">
              <div class="offset-lg-3 col-lg-7 d-flex align-items-right justify-content-end text-right">
                {{ __('events.before_submit_text') }}
              </div>
              <div class="col-lg-2 d-flex align-items-center justify-content-end mt-2 mt-lg-0">
                <b-btn variant="primary" class="break" type="submit" @click="submit">
                  {{ __('events.create_event') }}
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
import EventVenue from './EventVenue'
import EventGroup from './EventGroup'
import { required, minLength } from 'vuelidate/lib/validators'
import validationHelpers from '../mixins/validationHelpers'

// TODO Set initial values for duplicate event
// TODO Native inputs for date/time

function geocodeable() {
  return this.lat !== null && this.lng !== null
}

export default {
  components: {EventGroup, EventVenue, VenueAddress, EventTimeRangePicker, EventDatePicker, RichTextEditor},
  mixins: [event, auth, validationHelpers],
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
    groups: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      idgroups: null,
      eventVenue: null,
      free_text: null,
      eventDate: null,
      eventStart: null,
      eventEnd: null,
      eventOnline: false,
      eventAddress: null,
      lat: null,
      lng: null
    }
  },
  validations: {
    // TODO Any min/max lengths?
    // We use vuelidate to validate the inputs.  If necessary we pass the relevant validation down to a child component,
    // which is responsible for setting the hasError class.
    //
    // These need to match PartyController::create.
    idgroups: {
      required
    },
    eventVenue: {
      required,
    },
    free_text: {
      required
    },
    eventDate: {
      required
    },
    eventStart: {
      required
    },
    eventEnd: {
      required
    },
    eventAddress: {
      geocodeable
    }
  },
  computed: {
    CSRF () {
      return this.$store.getters['auth/CSRF']
    },
    allGroups () {
      return this.$store.getters['groups/list']
    },
  },
  created() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.$store.dispatch('groups/setList', {
      groups: this.groups
    })

    this.initialEvent.idevents = this.idevents
    this.$store.dispatch('events/set', this.initialEvent)

  },
  methods: {
    submit (e) {
      // Events are created via form submission - we don't yet have an API call to do this over AJAX.  Therefore
      // this page and the subcomponents have form inputs with suitable names.
      //
      // Check the form is valid.
      this.$v.$touch()

      if (this.$v.$invalid) {
        // It's not - prevent the submit.
        e.preventDefault()

        this.validationFocusFirstError()
      }
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

.layout {
  display: grid;
  grid-column-gap: 40px;

  grid-template-columns: 1fr;

  @include media-breakpoint-up(lg) {
    grid-template-columns: 2fr 1fr 1fr;
  }

  .event-venue {
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

.moveright {
  margin-left: 2px;
}

.movedown {
  margin-top: 2px;
}

/deep/ .hasError, /deep/ .card .form-control.hasError:focus {
  border: 2px solid $brand-danger !important;
}
</style>