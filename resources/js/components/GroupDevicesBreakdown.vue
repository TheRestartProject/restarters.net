<template>
  <div>
    <h2 class="mt-4">{{ __('groups.device_breakdown') }}</h2>
    <b-tabs class="ourtabs w-100 mt-4 d-none d-md-block" justified>
      <b-tab active class="pt-2">
        <template slot="title">
          <b>{{ __('strings.Computers and Home Office') }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[1]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ __('strings.Electronic Gadgets') }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[2]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ __('strings.Home Entertainment') }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[3]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ __('strings.Kitchen and Household Items') }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[4]" />
      </b-tab>
    </b-tabs>
    <div class="d-block d-md-none border-top-thick pt-3">
      <CollapsibleSection heading-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ __('strings.Computers and Home Office') }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[1]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed heading-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ __('strings.Electronic Gadgets') }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[2]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed heading-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ __('strings.Home Entertainment') }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[3]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed heading-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ __('strings.Kitchen and Household Items') }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[4]" />
        </template>
      </CollapsibleSection>
    </div>
    <div class="small mt-3">
      <p class="small text-brand">
        {{ __('groups.no_unpowered_stats') }}
      </p>
    </div>
  </div>
</template>
<script>
import GroupDevicesBreakdownCluster from './GroupDevicesBreakdownCluster.vue'
import CollapsibleSection from './CollapsibleSection.vue'
export default {
  components: {CollapsibleSection, GroupDevicesBreakdownCluster},
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    clusterStats: {
      type: Object,
      required: true
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

::v-deep .impact-stat-subtitle {
  font-size: 80%;
}

.border-top-thick {
  border-top: 5px solid $black;
}

.mobtitle {
  text-transform: uppercase;
  font-size: 18px;
}
</style>