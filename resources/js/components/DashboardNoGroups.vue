<template>
  <div>
    <div v-if="location" class="dng-layout mb-2">
      <div class="pic" />
      <!-- eslint-disable-next-line -->
      <div class="overlay">
        <div class="mt-2 m-md-2">
          <div v-html="__('dashboard.no_groups')" v-if="!nearbyGroups.length" />
          <div v-html="__('dashboard.no_groups_intro')" />
        </div>
      </div>
      <div class="groups mt-2 p-0 p-md-2" v-if="nearbyGroups.length">
        <h3>{{ __('dashboard.groups_near_you_header') }}</h3>
        <hr />
        <DashboardGroup v-for="group in nearbyGroups" :key="'nearbygroup-' + group.idgroups" :group="group" />
        <a href="/group/nearby">
          {{ __('dashboard.see_all_groups_near_you') }}
        </a>
      </div>
    </div>
    <div v-else class="layout mb-2">
      <div class="pic" />
      <!-- eslint-disable-next-line -->
      <div class="overlay">
        <div v-html="__('groups.no_groups_nearest_no_location')" class="mt-2 m-md-2" />
      </div>
    </div>
    <div class="text pr-md-2 pl-md-2 pb-2">
      <strong>{{ __('dashboard.interested_starting') }}</strong>
      <!-- eslint-disable-next-line -->
      <div v-html="__('dashboard.interested_details')" />
    </div>
  </div>
</template>
<script>
import DashboardGroup from './DashboardGroup'
export default {
  components: {DashboardGroup},
  props: {
    location: {
      type: String,
      required: true
    },
    nearbyGroups: {
      type: Array,
      required: false,
      default: null
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.dng-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto auto;

  .pic {
    grid-row: 2 / 3;
    grid-column: 1 / 2;
    height: 200px;
    margin-top: 1rem;
    margin-bottom: 1rem;
  }

  .overlay {
    grid-row: 1 / 2;
    grid-column: 1 / 2;
  }

  .groups {
    grid-row: 3 / 4;
    grid-column: 1 / 2;
  }

  .text {
    grid-row: 4 / 5;
    grid-column: 1 / 2;
  }

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;

    .overlay {
      grid-row: 1 / 1;
      grid-column: 1 / 2;
    }

    .pic {
      grid-row: 1 / 1;
      grid-column: 2 / 3;
      height: unset;
      margin-top: unset;
      margin-bottom: unset;
    }

    .groups {
      grid-row: 2 / 3;
      grid-column: 1 / 3;
    }

    .text {
      grid-row: 3 / 4;
      grid-column: 1 / 3;
    }
  }
}

.pic {
  overflow: hidden;
  background-size: cover;
  background-position: center;
  background-image: url('/images/no_groups.png');
}

h3 {
  font-size: 1rem;
  font-weight: bold;
}

hr {
  border-top: 1px solid black;
}
</style>