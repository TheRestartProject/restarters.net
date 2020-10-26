<template>
  <div>
    {{ clusterStats }}
    <h2 class="mt-4">{{ translatedDeviceBreakdown }}</h2>
    <b-tabs class="ourtabs w-100 mt-2" justified>
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
        <GroupDevicesBreakdownCluster :stats="clusterStats[1]" />
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b>{{ translatedKitchenAndHouseholdItems }}</b>
        </template>
        <GroupDevicesBreakdownCluster :stats="clusterStats[2]" />
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>
import GroupDevicesBreakdownCluster from './GroupDevicesBreakdownCluster'
export default {
  components: {GroupDevicesBreakdownCluster},
  props: {
    clusterStats: {
      type: Object,
      required: true
    },
  },
  computed: {
    translatedDeviceBreakdown() {
      return this.$lang.get('groups.device_breakdown')
    },
    translatedComputerAndHomeOffice() {
      return this.$lang.get('groups.computed_and_home_office')
    },
    translatedElectronicGadget() {
      return this.$lang.get('groups.electronic_gadget')
    },
    translatedHomeEntertainment() {
      return this.$lang.get('groups.home_entertainment')
    },
    translatedKitchenAndHouseholdItems() {
      return this.$lang.get('groups.kitchen_and_household_items')
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';
</style>