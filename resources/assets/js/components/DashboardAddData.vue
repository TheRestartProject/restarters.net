<template>
  <div class="bg">
    <CollapsibleSection class="p-3" :show-horizontal-rule="false" heading-class="">
      <template slot="title">
        <div class="d-flex">
          <div class="align-self-center">
            {{ translatedAddDataHeading }}
          </div>
          <b-img src="/images/fixometer_doodle.svg" class="ml-4 d-none d-md-block" />
        </div>
      </template>
      <template slot="content">
        <div class="content">
          <p>
            {{ translatedSeeYourImpact }}:
          </p>
          <div class="layout">
            <multiselect
                class="groups"
                v-model="groupValue"
                :options="groupOptions"
                track-by="idgroups"
                label="name"
                :allow-empty="false"
                deselectLabel=""
            >
            </multiselect>
            <multiselect
                class="events"
                v-model="eventValue"
                :options="eventOptions"
                track-by="idevents"
                label="name"
                :allow-empty="false"
                deselectLabel=""
            >
            </multiselect>
            <div class="addbutton d-flex justify-content-md-end">
              <div>
                <b-btn variant="primary" :href="'/party/view/' + eventValue.idevents + '#devices-section'">
                  {{ translatedAddDataAdd }}
                </b-btn>
              </div>
            </div>
          </div>
        </div>
      </template>
    </CollapsibleSection>
  </div>
</template>
<script>
import CollapsibleSection from './CollapsibleSection'
import moment from 'moment'

export default {
  data () {
    return {
      groupValue: null,
      eventValue: null
    }
  },
  components: {CollapsibleSection},
  computed: {
    groups() {
      let groups = this.$store.getters['groups/list']

      return groups ? groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
    events() {
      return Object.values(this.$store.getters['events/getAll'])
    },
    attendedEvents() {
      return this.events.filter(e => e.attended).sort((a,b) => new moment(b.event_date + ' ' + b.start).unix() - new moment(a.event_date + ' ' + a.start).unix())
    },
    groupEvents() {
      return this.events
          .filter(e => this.groupValue && e.idgroups === this.groupValue.idgroups && e.attended)

    },
    groupOptions() {
      // TODO Organising group?
      // We want to show the groups for events which we have attended.
      const ids = this.attendedEvents.map(e => e.idgroups)
      return this.groups.filter(g => ids.indexOf(g.idgroups) !== -1)
    },
    eventOptions() {
      return this.groupEvents.map(e => {
        return {
          idevents: e.idevents,
          name: new moment(e.event_date).format('DD MMM YY') + ' @ ' + e.venue
        }
      })
    },
    translatedAddDataHeading() {
      return this.$lang.get('dashboard.add_data_heading')
    },
    translatedSeeYourImpact() {
      return this.$lang.get('dashboard.see_your_impact')
    },
    translatedAddDataAdd() {
      return this.$lang.get('dashboard.add_data_add')
    },
  },
  mounted() {
    // Select first attended event
    if (this.attendedEvents.length) {
      const e = this.attendedEvents[0]

      this.eventValue = {
        idevents: e.idevents,
        name: new moment(e.event_date).format('DD MMM YY') + ' @ ' + e.venue
      }
      console.log("Event", this.eventValue)

      this.groupValue = this.groups.find(g => g.idgroups = e.idgroups)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.content {
  border-top: 3px dashed black;
  padding-top: 20px;
}

h3 {
  font-size: $font-size-base;
  font-weight: bold;
}

a {
  color: unset;
  text-decoration: underline;
}

.bg {
  background-color: $brand-light;
  box-shadow: 5px 5px $black;
}

.layout {
  display: grid;
  grid-template-rows: auto auto auto;
  grid-template-columns: auto;
  grid-row-gap: 20px;
  grid-column-gap: 0px;

  @include media-breakpoint-up(md) {
    grid-template-rows: auto;
    grid-template-columns: auto auto auto;
    grid-column-gap: 20px;
    grid-row-gap: 0px;
  }

  .groups {
    grid-row: 1 / 2;
    grid-column:  1 / 2;
    padding-bottom: 0px;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 2;
      grid-column:  1 / 2;
    }
  }

  .events {
    grid-row: 2 / 3;
    grid-column:  1 / 2;
    padding-bottom: 0px;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 2;
      grid-column:  2 / 3;
    }
  }

  .addbutton {
    grid-row: 3 / 3;
    grid-column:  1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 2;
      grid-column:  3 / 4;
    }
  }
}
</style>