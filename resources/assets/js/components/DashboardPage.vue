<template>
  <div>
    <div class="d-flex justify-content-center align-content-center mb-4">
      <b-img-lazy fluid src="/images/arrows_doodle.svg" />
      <h1 class="ml-2 mr-2 align-self-center">{{ translatedTitle }}</h1>
      <b-img-lazy fluid src="/images/confetti_doodle.svg" />
    </div>
    <div class="layout">
      <div class="banner">
        <DashboardBanner />
      </div>
      <DashboardYourGroups class="yourgroups mb-2" />
      <DashboardRightSidebar class="sidebar" />
    </div>
  </div>
</template>
<script>
import auth from '../mixins/auth'
import DashboardBanner from './DashboardBanner'
import DashboardYourGroups from './DashboardYourGroups'
import DashboardRightSidebar from './DashboardRightSidebar'

export default {
  components: {DashboardYourGroups,DashboardRightSidebar,DashboardBanner},
  mixins: [ auth ],
  props: {
    yourGroups: {
      type: Array,
      required: false,
      default: null
    },
    upcomingEvents: {
      type: Array,
      required: false,
      default: null
    },
  },
  data () {
    return {
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

    this.$store.dispatch('events/setList', {
      events: this.upcomingEvents
    })
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('dashboard.title')
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  grid-template-rows: auto auto auto;
  grid-column-gap: 20px;
  grid-template-columns: 2fr 1fr;

  .banner {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .yourgroups {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
  }

  .sidebar {
    grid-row: 1 / 4;
    grid-column: 2 / 3;
  }
}
</style>