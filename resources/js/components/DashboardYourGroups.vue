<template>
  <CollapsibleSection border-shadow class="p-3" :show-horizontal-rule="false" heading-class="">
    <template slot="title">
      <div class="d-flex justify-content-between flex-wrap">
        <div class="d-flex w-100">
          <div class="align-self-center">
            {{ __('dashboard.your_groups_heading') }}
          </div>
          <b-img class="height ml-4" src="/images/group_doodle_ico.svg" />
        </div>
        <a href="/group/nearby" v-if="newGroups && newGroups.length" class="added added-md d-none d-md-block pr-3">
          <b-img src="/images/arrow-right-doodle-white.svg" />
          {{ translatedNewlyAdded }}
        </a>
      </div>
    </template>

    <template slot="content">
      <div class="content">
        <DashboardNoGroups v-if="!myGroups || !myGroups.length" :nearby-groups="nearbyGroups" :location="location" />
        <div v-else>
          <a href="/group/nearby" v-if="newGroups && newGroups.length" class="added added-xs d-block d-md-none pr-3 pt-3 pb-3 mb-2">
            <b-img src="/images/arrow-right-doodle-white.svg" />
            {{ translatedNewlyAdded }}
          </a>
          <div class="dyg-layout">
            <div class="group-intro">
              <h3>
                {{ __('dashboard.groups_heading') }}
              </h3>
              <p>
              {{ __('dashboard.catch_up') }}
              </p>
            </div>
            <div class="group-list">
              <p class="border border-dark border-top-0 border-left-0 border-right-0" />
              <DashboardGroup
                  v-for="g in myGroups"
                  :group="g"
                  :key="g.idgroups"
              />
            </div>
            <div class="group-seeall">
              <div class="d-flex justify-content-end">
                <a href="/group" class="mr-1">
                  {{ __('dashboard.see_all_groups') }}
                </a>
              </div>
            </div>
            <div class="group-spacer" />
            <div class="event-intro">
              <div class="d-flex justify-content-between">
                <div>
                  <h3>
                    {{ __('dashboard.upcoming_events_title') }}
                  </h3>
                  <p v-if="events.length">
                    {{ __('dashboard.upcoming_events_subtitle') }}
                  </p>
                  <p v-else>
                    {{ __('events.no_upcoming_for_your_groups') }}.
                  </p>
                </div>
                <div>
                  <b-btn variant="primary" href="/party/create" class="text-nowrap">
                    {{ __('dashboard.add_event') }}
                  </b-btn>
                </div>
              </div>
            </div>
            <div class="event-list">
              <p class="border border-dark border-top-0 border-left-0 border-right-0" />
              <DashboardEvent v-for="e in events" :key="'event-' + e.idevents" :idevents="e.idevents" class="ml-1" />
            </div>
            <div class="event-seeall">
              <div class="d-flex justify-content-end">
                <a href="/party" class="mr-1">
                  {{ __('partials.see_all_events') }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import moment from 'moment'
import DashboardGroup from './DashboardGroup'
import CollapsibleSection from './CollapsibleSection'
import DashboardEvent from './DashboardEvent'
import DashboardNoGroups from './DashboardNoGroups'

export default {
  props: {
    location: {
      type: String,
      required: true
    },
    newGroups: {
      type: Array,
      required: true
    },
    nearbyGroups: {
      type: Array,
      required: false,
      default: null
    },
  },
  components: {DashboardNoGroups, DashboardEvent, CollapsibleSection, DashboardGroup},
  computed: {
    groups() {
      let groups = this.$store.getters['groups/list']

      return groups ? groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
    myGroups() {
      return this.groups.filter(g => {
        return g.ingroup
      })
    },
    events() {
      return this.$store.getters['events/getByGroup'](null).filter(e => e.upcoming).sort((a, b) => {
        // Sort soonest first.
        return new moment(a.event_start_utc).unix() - new moment(b.event_start_utc).unix()
      })
    },
    translatedNewlyAdded() {
      return this.$lang.choice('dashboard.newly_added', this.newGroups.length, {
        count: this.newGroups ? this.newGroups.length : 0
      })
    },
  },
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

::v-deep a {
  color: $brand;
  text-decoration: underline;
}

.dyg-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto 40px auto auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr;
    grid-template-rows: auto auto auto;
  }

  .group-intro {
    grid-row: 1 / 2;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
    }
  }

  .group-list {
    grid-row: 2 / 3;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
    }
  }

  .group-seeall {
    grid-row: 3 / 4;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
    }
  }

  .group-spacer {
    grid-row: 4 / 5;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 1;
      grid-column: 1 / 1;
    }
  }

  .event-intro {
    grid-row: 6 / 7;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 2;
      grid-column: 3 / 4;
    }
  }

  .event-list {
    grid-row: 7 / 8;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 2 / 3;
      grid-column: 3 / 4;
    }
  }

  .event-seeall {
    grid-row: 8 / 9;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 3 / 4;
      grid-column: 3 / 4;
    }
  }
}

.added {
  font-size: 75%;
  background-color: $black;
  color: white;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  align-self: center;
  text-decoration: none;

  &.added-md {
    transform: translateX(1.1rem);
    padding-left: 0px;
    line-height: 3rem;
  }
}
</style>