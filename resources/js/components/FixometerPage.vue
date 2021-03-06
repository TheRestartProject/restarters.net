<template>
  <div class="mb-2">
    <FixometerHeading />
    <FixometerGlobalImpact :latest-data="latestData" :impact-data="impactData" class="mt-4" />
    <hr class="mt-md-50 hr-dashed">

    <div class="d-flex justify-content-between">
      <h2 class>
        {{ __('devices.repair_records') }}
      </h2>
      <div v-if="isAdmin">
        <b-btn variant="primary" href="/export/devices/?">
          {{ __('devices.export_device_data') }}
        </b-btn>
      </div>
    </div>
    <p>{{ __('devices.search_text') }}</p>
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
          :wiki.sync="wiki"
          :status.sync="status"
          :group.sync="group"
          :from_date.sync="from_date"
          :to_date.sync="to_date"
          :start-expanded-items="startExpandedItems"
          :start-expanded-events="startExpandedEvents"
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
          :wiki.sync="wiki"
          :status.sync="status"
          :group.sync="group"
          :from_date.sync="from_date"
          :to_date.sync="to_date"
          :start-expanded-items="startExpandedItems"
          :start-expanded-events="startExpandedEvents"
      />
      <b-tabs class="ourtabs ourtabs-brand w-100 d-none d-md-block" v-model="tabIndex">
        <b-tab active title-item-class="w-50" title-link-class="smallpad" class="pt-2">
          <template slot="title">
            <div class="d-flex justify-content-between">
              <div>
                <b>{{ __('devices.title_powered') }}</b> ({{ powered_total.toLocaleString() }})
              </div>
              <div class="d-flex text-brand font-weight-bold">
                <div class="mr-3 lower">
                  <b-img src="/images/trash_brand.svg" class="icon" />
                  {{ powered_weight.toLocaleString() }} kg
                </div>
                <div class="mr-1 lower">
                  <b-img src="/images/co2_brand.svg" class="icon" />
                  {{ powered_co2.toLocaleString() }} kg
                </div>
              </div>
            </div>
          </template>
          <p class="pl-3" v-html="__('devices.description_powered')" />
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :itemTypes="itemTypes"
              :category="category_powered"
              :brand="brand"
              :model="model"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="powered_total"
              :weight.sync="powered_weight"
              :co2.sync="powered_co2"
          />
        </b-tab>
        <b-tab title-item-class="w-50" title-link-class="smallpad" class="pt-2">
          <template slot="title">
            <div class="d-flex justify-content-between">
              <div>
                <b>{{ __('devices.title_unpowered') }}</b> ({{ unpowered_total.toLocaleString() }})
              </div>
              <div class="lower text-brand font-weight-bold">
                <b-img src="/images/trash_brand.svg" class="icon" />
                {{ unpowered_weight.toLocaleString() }} kg
              </div>
            </div>
          </template>
          <p class="pl-3" v-html="__('devices.description_unpowered')" />
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="false"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :itemTypes="itemTypes"
              :category="category_unpowered"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="unpowered_total"
              :weight.sync="unpowered_weight"
              :co2.sync="unpowered_co2"
          />
        </b-tab>
      </b-tabs>
    </div>
    <div class="d-block d-md-none">
      <CollapsibleSection collapsed :count="powered_total" heading-level="h6" count-class="small">
        <template slot="title">
          {{ __('devices.title_powered') }}
        </template>
        <template slot="title-right">
          <div class="small mt-2">
            <div class="d-flex text-brand font-weight-bold small">
              <div class="mr-3 lower d-flex align-content-center">
                <b-img src="/images/trash_brand.svg" class="iconsmall" />
                <span class="mb-1">
                  {{ powered_weight.toLocaleString() }}
                </span>
              </div>
              <div class="mr-1 lower d-flex">
                <b-img src="/images/co2_brand.svg" class="iconsmall" />
                <span class="mb-1">
                  {{ powered_co2.toLocaleString() }}
                </span>
              </div>
            </div>
          </div>
        </template>
        <template slot="content">
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :itemTypes="itemTypes"
              :category="category_powered"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="powered_total"
              :weight.sync="powered_weight"
              :co2.sync="powered_co2"
          />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed :count="unpowered_total" heading-level="h6" count-class="small">
        <template slot="title">
          {{ __('devices.title_unpowered') }}
        </template>
        <template slot="title-right">
          <div class="small mt-2">
            <div class="d-flex text-brand font-weight-bold small">
              <div class="mr-1 lower d-flex">
                <b-img src="/images/trash_brand.svg" class="iconsmall" />
                <span class="mb-1">
                  {{ unpowered_weight.toLocaleString() }}
                </span>
              </div>
            </div>
          </div>
        </template>
        <template slot="content">
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="false"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :itemTypes="itemTypes"
              :category="category_unpowered"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="unpowered_total"
              :weight.sync="unpowered_weight"
              :co2.sync="unpowered_co2"
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
import auth from '../mixins/auth'

export default {
  components: {CollapsibleSection, FixometerFilters, FixometerRecordsTable, FixometerGlobalImpact, FixometerHeading},
  mixins: [ auth ],
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
    itemTypes: {
      type: Array,
      required: false,
      default: null
    },
    isAdmin: {
      type: Boolean,
      required: true
    }
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
      wiki: null,
      group: null,
      from_date: null,
      to_date: null,

      startExpandedItems: false,
      startExpandedEvents: false,

      powered_total: 0,
      unpowered_total: 0,
      powered_weight: 0,
      unpowered_weight: 0,
      powered_co2: 0,
      unpowered_co2: 0,
    }
  },
  created() {
    // Apply any URL paramters.
    //
    // We have to list each of these individually for reactivity to notice them.
    const params = (new URL(document.location)).searchParams

    if (params.has('category_powered')) {
      this.category_powered = parseInt(params.get('category_powered'))
      this.startExpandedItems = true
    }

    if (params.has('category_unpowered')) {
      this.category_unpowered = parseInt(params.get('category_unpowered'))
      this.startExpandedItems = true
    }

    if (params.has('status')) {
      this.status = parseInt(params.get('status'))
      this.startExpandedItems = true
    }

    if (params.has('brand')) {
      this.brand = params.get('brand')
      this.startExpandedItems = true
    }

    if (params.has('model')) {
      this.model = params.get('model')
      console.log("Got modal ", this.model)
      this.startExpandedItems = true
    }

    if (params.has('item_type')) {
      this.item_type = params.get('item_type')
      this.startExpandedItems = true
    }

    if (params.has('comments')) {
      this.comments = params.get('comments')
      this.startExpandedItems = true
    }

    if (params.has('wiki')) {
      this.wiki = params.get('wiki')
      this.startExpandedItems = true
    }

    if (params.has('group')) {
      this.group = params.get('group')
      this.startExpandedEvents = true
    }

    if (params.has('from_date')) {
      this.from_date = params.get('from_date')
      this.startExpandedEvents = true
    }

    if (params.has('to_date')) {
      this.to_date = params.get('to_date')
      this.startExpandedEvents = true
    }
  },
  watch: {
    url(newVal) {
      try {
        window.history.pushState({ path: newVal }, window.title, newVal );
      }
      catch (ex) {
        console.warn(ex);
      }
    }
  },
  computed: {
    url() {
      // We want to change the URL.  In a full app the router would handle this.
      //
      // We have to list each of these individually for reactivity to notice them.
      let ret = ''

      if (this.category_powered) {
        ret += 'category_powered=' + encodeURIComponent(this.category_powered) + '&'
      }

      if (this.category_unpowered) {
        ret += 'category_unpowered=' + encodeURIComponent(this.category_unpowered) + '&'
      }

      if (this.status) {
        ret += 'status=' + encodeURIComponent(this.status) + '&'
      }

      if (this.brand) {
        ret += 'brand=' + encodeURIComponent(this.brand) + '&'
      }

      if (this.model) {
        ret += 'model=' + encodeURIComponent(this.model) + '&'
      }

      if (this.item_type) {
        ret += 'item_type=' + encodeURIComponent(this.item_type) + '&'
      }

      if (this.comments) {
        ret += 'comments=' + encodeURIComponent(this.comments) + '&'
      }

      if (this.wiki) {
        ret += 'wiki=' + encodeURIComponent(this.wiki) + '&'
      }

      if (this.group) {
        ret += 'group=' + encodeURIComponent(this.group) + '&'
      }

      if (this.from_date) {
        ret += 'from_date=' + encodeURIComponent(this.from_date) + '&'
      }

      if (this.to_date) {
        ret += 'to_date=' + encodeURIComponent(this.to_date) + '&'
      }

      if (ret !== '') {
        ret = '/fixometer?' + ret
      } else {
        ret = '/fixometer'
      }

      return ret
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
  height: 15px;
  margin-bottom: 5px;
}

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto;
  grid-column-gap: 0px;
  grid-row-gap: 20px;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 3fr;
    grid-template-rows: auto;
    grid-column-gap: 20px;
    grid-row-gap: 0px;
  }
}
</style>