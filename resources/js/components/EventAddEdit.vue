<template>
  <b-form :action="formAction" method="post">
    <input type="hidden" name="_token" :value="CSRF" :enctype="encType" />
    <input type="hidden" name="id" :value="initialEvent.idevents" v-if="!creating" />
    <input type="hidden" name="event_start_utc" :value="eventStartUtc" />
    <input type="hidden" name="event_end_utc" :value="eventEndUtc" />

    <div class="layout">
      <EventVenue
          class="flex-grow-1 event-venue"
          :venue.sync="eventVenue"
          :online.sync="eventOnline"
          :has-error="$v.eventVenue.$error"
          ref="eventVenue"/>
      <EventLink
          class="flex-grow-1 event-link"
          :link.sync="eventLink"
          :has-error="$v.eventLink.$error"
          ref="eventLink"/>
      <EventGroup
          class="event-group"
          :value.sync="idgroups"
          :has-error="$v.idgroups.$error"
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
      <div class="event-approve" v-if="canApprove">
        <b-form-group>
          <label class="groups-tags-label" for="moderate"><svg width="18" height="18" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"></path><ellipse cx="6.472" cy=".217" rx=".274" ry=".217"></ellipse><ellipse cx="8.528" cy=".217" rx=".274" ry=".217"></ellipse><path d="M6.472 0h2.056v1.394H6.472z"></path><path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"></path><ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"></ellipse><ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"></ellipse><path d="M6.472 13.606h2.056V15H6.472z"></path><path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"></path><path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"></path><path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"></path><path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"></path><path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"></path><path d="M0 6.472v2.056h1.394V6.472H0z"></path><path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"></path><path d="M15 8.528V6.472h-1.394v2.056H15z"></path><path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"></path><path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"></path><path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"></path><path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"></path><path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"></path></g></svg> {{ __('events.approve_event') }}</label>
          <b-select v-model="moderate" name="moderate">
            <option></option>
            <option value="approve">Approve</option>
          </b-select>

          <small id="locationHelpBlock" class="form-text text-muted" v-if="moderate === 'approve'">
            This will mark the post as having been moderated and will send all hosts an email confirming
          </small>
        </b-form-group>
      </div>
      <div class="event-buttons button-group row" v-if="creating">
        <div class="offset-lg-3 col-lg-7 d-flex align-items-right justify-content-end text-right">
          <span v-if="autoApprove">
            {{ __('events.before_submit_text_autoapproved') }}
          </span>
          <span v-else>
            {{ __('events.before_submit_text') }}
          </span>
        </div>
        <div class="col-lg-2 d-flex align-items-center justify-content-end mt-2 mt-lg-0">
          <b-btn variant="primary" class="break" type="submit" @click="submit">
            {{ __('events.create_event') }}
          </b-btn>
        </div>
      </div>
      <div  class="event-buttons button-group row" v-else>
        <div class="offset-lg-3 col-lg-5 d-flex align-items-right justify-content-end text-right notice">
          <span v-if="autoApprove">
            {{ __('events.before_submit_text_autoapproved') }}
          </span>
          <span v-else>
            {{ __('events.before_submit_text') }}
          </span>
        </div>
        <div class="col-lg-4 d-flex align-items-center justify-content-end mt-2 mt-lg-0">
          <b-btn :href="'/party/duplicate/' + initialEvent.idevents" variant="primary" size="md" class="mr-2 duplicate">
            {{ __('events.duplicate_event') }}
          </b-btn>
          <b-btn variant="primary" class="break submit" type="submit" @click="submit">
            {{ __('events.save_event') }}
          </b-btn>
        </div>
      </div>
    </div>
  </b-form>
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
import EventLink from './EventLink'
import { required, url, helpers } from 'vuelidate/lib/validators'
import validationHelpers from '../mixins/validationHelpers'
import moment from 'moment-timezone'

function geocodeableValidation() {
  return this.lat !== null && this.lng !== null
}

const timeValidator = helpers.regex('timeValidator', /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)

export default {
  components: {EventGroup, EventVenue, EventLink, VenueAddress, EventTimeRangePicker, EventDatePicker, RichTextEditor},
  mixins: [event, auth, validationHelpers],
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
    },
    canApprove: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      idgroups: null,
      eventVenue: null,
      eventLink: null,
      free_text: null,
      eventDate: null,
      eventStart: null,
      eventEnd: null,
      eventOnline: false,
      eventAddress: null,
      lat: null,
      lng: null,
      moderate: null
    }
  },
  validations: {
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
      required,
      timeValidator
    },
    eventEnd: {
      required,
      timeValidator
    },
    eventAddress: {
      geocodeableValidation
    },
    eventLink: {
      url
    }
  },
  computed: {
    creating() {
      return !this.initialEvent
    },
    formAction() {
      return this.creating ? "/party/create" : ("/party/edit/" + this.initialEvent.idevents)
    },
    encType() {
      return this.creating ? null : 'enctype="multipart/form-data"'
    },
    CSRF () {
      return this.$store.getters['auth/CSRF']
    },
    allGroups () {
      return this.$store.getters['groups/list']
    },
    group() {
      return this.allGroups.find(g => g.idgroups === this.idgroups)
    },
    autoApprove() {
      return this.group ? this.group.auto_approve : false
    },
    // The server expects the UTC versions of the data.
    eventStartUtc() {
      if (!this.eventDate || !this.eventStart || !this.group) {
        return null
      }

      const m = moment.tz(this.eventDate + ' ' + this.eventStart, this.group.timezone)
      return m.toISOString()
    },
    eventEndUtc() {
      if (!this.eventDate || !this.eventEnd || !this.group) {
        return null
      }

      const m = moment.tz(this.eventDate + ' ' + this.eventEnd, this.group.timezone)
      return m.toISOString()
    }
  },
  created() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.$store.dispatch('groups/setList', {
      groups: this.groups
    })

    let setFrom = null

    if (this.duplicateFrom) {
      setFrom = this.duplicateFrom
    } else if (this.initialEvent) {
      setFrom = this.initialEvent
    }

    if (setFrom) {
      this.idgroups = setFrom.group.idgroups
      this.eventVenue = setFrom.venue
      this.eventLink = setFrom.link
      this.eventAddress = setFrom.location
      this.free_text = setFrom.free_text
      this.eventStart = setFrom.start_local
      this.eventEnd = setFrom.end_local
      this.eventOnline = setFrom.online ? true : false
      this.lat = setFrom.latitude
      this.lng = setFrom.longitude
    }

    if (this.initialEvent) {
      // We deliberately don't set the date above, because we don't want it set for event duplication.
      //
      // The date we get here is epoch.
      this.eventDate = new Date(this.initialEvent.event_start_utc).toISOString().slice(0, 10)
    }

    // If only one group, default to that.
    if (this.groups && this.groups.length === 1) {
      this.idgroups = this.groups[0].idgroups
    }

    // Normally we pass the CSRF into the top-level page component.  But this component is used both from a
    // top-level page (event create) and within a more complex page structure (event edit).  So we get the CSRF
    // here.  Because we have overridden the create() method of the mixin we need to put the code in here.
    if (this.csrf) {
      this.$store.dispatch('auth/setCSRF', {
        CSRF: this.csrf
      })
    } else {
      console.error("No CSRF provided by blade template")
    }
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
        console.log("Not valid", this.$v)
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
    grid-template-columns: 2fr 1.5fr 1fr;
  }

  .event-venue {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .event-link {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
    margin-right: 2px;
  }

  .event-group {
    grid-row: 3 / 4;
    grid-column: 1 / 2;
  }

  .event-description {
    grid-row: 4 / 5;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 4 / 7;
    }
  }

  .event-date {
    grid-row: 5 / 6;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 1 / 2;
      grid-column: 2 / 3;
    }
  }

  .event-time {
    grid-row: 6 / 7;
    grid-column: 1 / 2;

    @include media-breakpoint-up(lg) {
      grid-row: 2 / 3;
      grid-column: 2 / 3;
    }
  }

  .event-address {
    grid-row: 7 / 8;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 3 / 4;
      grid-column: 2 / 4;
    }
  }

  .event-approve {
    grid-row: 8 / 9;
    grid-column: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 5 / 6;
      grid-column: 2 / 4;
    }
  }

  .event-buttons {
    grid-row: 9 / 10;
    grid-colum: 1 / 2;

    /deep/ .btn {
      font-size: 16px;
    }

    @include media-breakpoint-up(lg) {
      grid-row: 7 / 8;
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
  margin: 0px !important;
}

.notice {
  font-size: 15px;
}

/deep/ .ql-toolbar button {
  width: 30px !important;
}
</style>