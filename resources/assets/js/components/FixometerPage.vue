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
    <p>
      TODO Filter on wiki - question with James.
      TODO Update tab titles to reflect search filters.
      TODO Link to URL for search filters
      TODO Button to share link.
      TODO Mobile view
    </p>
    <div class="layout">
      <FixometerFilters
          v-show="tabIndex === 0"
          :clusters="clusters"
          :brands="brands"
          :powered="true"
          :category.sync="category_powered"
          :brand.sync="brand"
          :model.sync="model"
          :item_type.sync="item_type"
          :comments.sync="comments"
          :status.sync="status"
          :group.sync="group"
          :from_date.sync="from_date"
          :to_date.sync="to_date"
      />
      <FixometerFilters
          v-show="tabIndex === 1"
          :clusters="clusters"
          :brands="brands"
          :powered="false"
          :category.sync="category_unpowered"
          :brand.sync="brand"
          :model.sync="model"
          :item_type.sync="item_type"
          :comments.sync="comments"
          :status.sync="status"
          :group.sync="group"
          :from_date.sync="from_date"
          :to_date.sync="to_date"
      />
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
          <FixometerRecordsTable
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_powered"
              :brand="brand"
              :model="model"
              :comments="comments"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
          />
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
          <FixometerRecordsTable
              :powered="false"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_unpowered"
              :item_type="item_type"
              :comments="comments"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
          />
        </b-tab>
      </b-tabs>
    </div>
    <div class="d-block d-md-none">
      <CollapsibleSection collapsed :count="impactData.total_powered" heading-level="h6" count-class="small">
        <template slot="title">
          {{ translatedPowered }}
        </template>
        <template slot="title-right">
          <div class="small mt-1">
            <div class="d-flex text-brand font-weight-bold">
              <div class="mr-3 lower d-flex align-content-center">
                <b-img src="/images/trash_brand.svg" class="iconsmall" />
                <span class="mt-1">
                  {{ impactData.ewaste.toLocaleString() }}
                </span>
              </div>
              <div class="mr-1 lower d-flex">
                <b-img src="/images/co2_brand.svg" class="iconsmall" />
                <span class="mt-1">
                  {{ impactData.emissions.toLocaleString() }}
                </span>
              </div>
            </div>
          </div>
        </template>
        <template slot="content">
          <FixometerRecordsTable
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_unpowered"
          />
        </template>
      </CollapsibleSection>
    </div>
  </div>
</template>
<script>
import FixometerHeading from './FixometerHeading'
import FixometerGlobalImpact from './FixometerGlobalImpact'
import FixometerRecordsTable from './FixometerRecordsTable'
import FixometerFilters from './FixometerFilters'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, FixometerFilters, FixometerRecordsTable, FixometerGlobalImpact, FixometerHeading},
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
      tabIndex: 0,
      category_powered: null,
      category_unpowered: null,
      status: null,
      brand: null,
      model: null,
      item_type: null,
      comments: null,
      group: null,
      from_date: null,
      to_date: null
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

.iconsmall {
  height: 25px;
  margin-bottom: 5px;
}

.layout {
  display: grid;
  grid-template-columns: 1fr 3fr;
  grid-column-gap: 20px;
}
</style>