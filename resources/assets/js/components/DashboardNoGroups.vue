<template>
  <div class="layout">
    <b-img-lazy src="/images/no_groups.png" class="pic w-100" />
    <!-- eslint-disable-next-line -->
    <div class="overlay p-2">
      <div v-html="translatedNoGroups" class="text-center text-white" />
    </div>
    <div class="group p-2">
      <h3>{{ translatedGroupsNearYou }}</h3>
      <hr />
      <DashboardGroup v-for="group in nearbyGroups" :key="'nearbygroup-' + group.idgroups" :group="group" />
    </div>
    <div class="text pr-2 pl-2 pb-2">
      <strong>{{ translatedInterestedStarting }}</strong>
      <!-- eslint-disable-next-line -->
      <div v-html="translatedInterestedDetails" />
    </div>
  </div>
</template>
<script>
import DashboardGroup from './DashboardGroup'
export default {
  components: {DashboardGroup},
  props: {
    nearbyGroups: {
      type: Array,
      required: false,
      default: null
    },
  },
  computed: {
    translatedNoGroups() {
      return this.$lang.get('dashboard.no_groups')
    },
    translatedInterestedStarting() {
      return this.$lang.get('dashboard.interested_starting')
    },
    translatedInterestedDetails() {
      return this.$lang.get('dashboard.interested_details')
    },
    translatedGroupsNearYou() {
      return this.$lang.get('dashboard.groups_near_you_header')
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto;

  .pic, .overlay {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .groups {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
  }

  .text {
    grid-row: 3 / 4;
    grid-column: 1 / 2;
  }

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;

    .pic, .overlay {
      grid-row: 1 / 3;
      grid-column: 1 / 2;
    }

    .groups {
      grid-row: 1 / 2;
      grid-column: 2 / 3;
    }

    .text {
      grid-row: 2 / 3;
      grid-column: 2 / 3;
    }
  }
}

/deep/ .overlay a {
  text-decoration: underline;
  color: white !important;
}

h3 {
  font-size: 1rem;
  font-weight: bold;
}

hr {
  border-top: 1px solid black;
}
</style>