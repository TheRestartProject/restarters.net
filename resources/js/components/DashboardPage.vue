<template>
  <div>
    <div class="d-flex justify-content-center align-content-center">
      <b-img-lazy fluid src="/images/arrows_doodle.svg" class="d-none d-md-block" />
      <h1 class="ml-2 mr-2 align-self-center">{{ __('dashboard.title') }}</h1>
      <b-img-lazy fluid src="/images/confetti_doodle.svg" class="d-none d-md-block" />
    </div>
    <div class="dp-layout mt-4 mb-4">
      <AlertBanner class="banner" />
      <div class="yourgroups">
        <DashboardYourGroups :newGroups="newGroups" :nearbyGroups="nearbyGroups" :location="location" />
      </div>
      <DashboardAddData class="adddata justify-self-end" />
      <DashboardRightSidebar class="sidebar" />
      <DiscourseDiscussion
          class="discourse"
          :see-all-topics-link="seeAllTopicsLink"
          :discourse-base-url="discourseBaseUrl"
          :is-logged-in="isLoggedIn"
      />
    </div>
  </div>
</template>
<script>
import auth from '../mixins/auth'
import AlertBanner from './AlertBanner'
import DashboardYourGroups from './DashboardYourGroups'
import DashboardRightSidebar from './DashboardRightSidebar'
import DiscourseDiscussion from './DiscourseDiscussion'
import DashboardAddData from './DashboardAddData'

export default {
  components: {DashboardAddData, DashboardYourGroups,DashboardRightSidebar,AlertBanner,DiscourseDiscussion},
  mixins: [ auth ],
  props: {
    yourGroups: {
      type: Array,
      required: false,
      default: null
    },
    location: {
      type: String,
      required: true
    },
    nearbyGroups: {
      type: Array,
      required: false,
      default: null
    },
    upcomingEvents: {
      type: Array,
      required: false,
      default: null
    },
    seeAllTopicsLink: {
      type: String,
      required: true
    },
    isLoggedIn: {
      type: Boolean,
      required: true
    },
    discourseBaseUrl: {
      type: String,
      required: true
    },
    newGroups: {
      type: Array,
      required: true
    }
  },
  created() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    let groups = {}

    this.yourGroups.forEach(g => {
      groups[g.idgroups] = g
      groups[g.idgroups].ingroup = true
    })

    this.$store.dispatch('groups/setList', {
      groups: Object.values(groups)
    })

    let events = {}

    if (this.upcomingEvents) {
      this.upcomingEvents.forEach(e => {
        events[e.idevents] = e
        e.group = e.the_group
        delete e.the_group
        e.upcoming = true
      })
    }

    this.$store.dispatch('events/setList', {
      events: Object.values(events)
    })
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.dp-layout {
  display: grid;
  grid-template-rows: auto auto auto auto;
  grid-template-columns: 1fr;

  @include media-breakpoint-up(md) {
    grid-template-rows: auto auto auto 40px auto;
    grid-column-gap: 20px;
    grid-template-columns: 2fr 1fr;
  }

  .banner {
    grid-row: 1 / 2;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 1 / 2;
      grid-column: 1 / 3;
    }
  }

  .yourgroups {
    grid-row: 3 / 4;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 2 / 3;
      grid-column: 1 / 2;
    }
  }

  .adddata {
    grid-row: 4 / 5;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 3 / 4;
      grid-column: 1 / 2;
    }
  }

  .discourse {
    grid-row: 5 / 6;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 5 / 6;
      grid-column: 1 / 3;
    }
  }

  .sidebar {
    grid-row: 2 / 3;
    grid-column: 1 / 2;

    @include media-breakpoint-up(md) {
      grid-row: 2 / 4;
      grid-column: 2 / 3;
    }
  }
}
</style>
