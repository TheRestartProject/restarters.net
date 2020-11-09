<template>
  <div>
    <h2 class="mt-4">{{ translatedDeviceBreakdown }}</h2>
    <b-tabs class="ourtabs w-100 mt-4 d-none d-md-block" justified>
      <b-tab active class="pt-2">
        <template slot="title">
          <b>{{ translatedComputerAndHomeOffice }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[1]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ translatedElectronicGadget }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[2]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ translatedHomeEntertainment }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[3]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ translatedKitchenAndHouseholdItems }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[4]" />
      </b-tab>
    </b-tabs>
    <div class="d-block d-md-none border-top-thick pt-3">
      <CollapsibleSection header-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ translatedComputerAndHomeOffice }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[1]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed header-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ translatedElectronicGadget }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[2]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed header-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ translatedHomeEntertainment }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[3]" />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed header-level="h3">
        <template slot="title">
          <span class="mobtitle">
            {{ translatedKitchenAndHouseholdItems }}
          </span>
        </template>
        <template slot="content">
          <GroupDevicesBreakdownCluster :stats="clusterStats[4]" />
        </template>
      </CollapsibleSection>
    </div>
    <div class="small mt-3">
      <p class="small text-brand">
        {{ translatedNoUnpoweredStats }}
      </p>
    </div>
  </div>
</template>
<script>
import GroupDevicesBreakdownCluster from './GroupDevicesBreakdownCluster'
import CollapsibleSection from './CollapsibleSection'
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
  computed: {
    translatedDeviceBreakdown() {
      return this.$lang.get('groups.device_breakdown')
    },
    // The cluster names are translated in the en.json file, or language variant.
    translatedComputerAndHomeOffice() {
      return this.$lang.get('strings.Computers and Home Office')
    },
    translatedElectronicGadget() {
      return this.$lang.get('strings.Electronic Gadgets')
    },
    translatedHomeEntertainment() {
      return this.$lang.get('strings.Home Entertainment')
    },
    translatedKitchenAndHouseholdItems() {
      return this.$lang.get('strings.Kitchen and Household Items')
    },
    translatedNoUnpoweredStats() {
      return this.$lang.get('groups.no_unpowered_stats')
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

/deep/ .impact-stat-subtitle {
  font-size: 80%;
}

.border-top-thick {
  border-top: 5px solid $black;
}

.mobtitle {
  text-transform: uppercase;
  font-size: 18px;
}

// Some cluster names are a bit long in English, so make more space.
/deep/ .ourtabs .nav-link {
  padding-left: calc(5% + 1px) !important;
}
</style>