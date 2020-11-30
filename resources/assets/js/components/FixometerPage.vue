<template>
  <div class="mb-2">
    <FixometerHeading />
    <FixometerGlobalImpact :latest-data="latestData" :impact-data="impactData" class="mt-4" />
    <hr class="mt-md-50 hr-dashed">

    <div class="d-flex justify-content-between">
      <h2 class>
        {{ translatedRestartRecords }}
      </h2>
      <div>
        <b-btn variant="primary" href="/export/devices/?">
          {{ translatedExportDeviceData}}
        </b-btn>
      </div>
    </div>
    <p>{{ translatedSearchText}}</p>
    <b-row>
      <b-col cols="3">
        <FixometerFilters :clusters="clusters" :brands="brands" :powered="tabIndex === 0"/>
      </b-col>
      <b-col cols="9">
        <b-tabs class="ourtabs ourtabs-brand w-100" v-model="tabIndex">
          <b-tab active title-item-class="w-50" title-link-class="smallpad" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedPowered }}</b> ({{ impactData.total_powered.toLocaleString() }})
                </div>
                <div class="d-flex text-brand font-weight-bold">
                  <div class="mr-3 lower">
                    <b-img src="/images/trash_brand.svg" class="icon" />
                    {{ impactData.ewaste.toLocaleString() }} kg
                  </div>
                  <div class="mr-1 lower">
                    <b-img src="/images/co2_brand.svg" class="icon" />
                    {{ impactData.emissions.toLocaleString() }} kg
                  </div>
                </div>
              </div>
            </template>
            <p class="pl-3" v-html="translatedDescriptionPowered" />
            <FixometerRecordsTable :powered="true" :total="impactData.total_powered" :clusters="clusters" :brands="brands" :barrier-list="barrierList" />
          </b-tab>
          <b-tab title-item-class="w-50" title-link-class="smallpad" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedUnpowered }}</b> ({{ impactData.total_unpowered.toLocaleString() }})
                </div>
                <div class="lower text-brand font-weight-bold">
                  <b-img src="/images/trash_brand.svg" class="icon" />
                  {{ impactData.unpowered_waste.toLocaleString() }} kg
                </div>
              </div>
            </template>
            <p class="pl-3" v-html="translatedDescriptionUnpowered" />
            <FixometerRecordsTable :powered="false" :total="impactData.total_unpowered" :clusters="clusters" :brands="brands" :barrier-list="barrierList" />
          </b-tab>
        </b-tabs>
      </b-col>
    </b-row>
  </div>
</template>
<script>
import FixometerHeading from './FixometerHeading'
import FixometerGlobalImpact from './FixometerGlobalImpact'
import FixometerRecordsTable from './FixometerRecordsTable'
import FixometerFilters from './FixometerFilters'

export default {
  components: {FixometerFilters, FixometerRecordsTable, FixometerGlobalImpact, FixometerHeading},
  props: {
    latestData: {
      type: Object,
      required: true
    },
    impactData: {
      type: Object,
      required: true
    },
    clusters: {
      type: Array,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: false,
      default: null
    },
    barrierList: {
      type: Array,
      required: false,
      default: null
    },
  },
  data () {
    return {
      tabIndex: 1
    }
  },
  mounted() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
  },
  computed: {
    translatedRestartRecords() {
      return this.$lang.get('devices.repair_records')
    },
    translatedSearchText() {
      return this.$lang.get('devices.search_text')
    },
    translatedExportDeviceData() {
      return this.$lang.get('devices.export_device_data')
    },
    translatedPowered() {
      return this.$lang.get('devices.title_powered')
    },
    translatedUnpowered() {
      return this.$lang.get('devices.title_unpowered')
    },
    translatedDescriptionPowered() {
      return this.$lang.get('devices.description_powered')
    },
    translatedDescriptionUnpowered() {
      return this.$lang.get('devices.description_unpowered')
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