<template>
  <div class="mb-2">
    <AlertBanner />
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
    <div class="fp-layout">
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
          @expandItems="startExpandedItems = $event"
          @expandEvents="startExpandedEvents = $event"
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
          @expandItems="startExpandedItems = $event"
          @expandEvents="startExpandedEvents = $event"
      />
      <b-tabs class="ourtabs ourtabs-brand w-100 d-none d-md-block" v-model="tabIndex">
        <b-tab active title-item-class="w-50" title-link-class="smallpad" class="pt-2">
          <template slot="title">
            <div>
              <b>{{ __('devices.title_powered') }}</b>
              ({{ total_powered.toLocaleString() }})
            </div>
          </template>
          <p class="pl-3" v-html="__('devices.description_powered')" />
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_powered"
              :brand="brand"
              :model="model"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="total_powered"
          />
        </b-tab>
        <b-tab title-item-class="w-50" title-link-class="smallpad" class="pt-2">
          <template slot="title">
            <div>
              <b>{{ __('devices.title_unpowered') }}</b>
              ({{ total_unpowered.toLocaleString() }})
            </div>
          </template>
          <p class="pl-3" v-html="__('devices.description_unpowered')" />
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="false"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_unpowered"
              :model="model"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="total_unpowered"
          />
        </b-tab>
      </b-tabs>
    </div>
    <div class="d-block d-md-none">
      <CollapsibleSection collapsed :count="impactData.total_powered" heading-level="h6" count-class="small">
        <template slot="title">
          {{ __('devices.title_powered') }}
        </template>
        <template slot="content">
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="true"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_powered"
              :brand="brand"
              :model="model"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="total_powered"
          />
        </template>
      </CollapsibleSection>
      <CollapsibleSection collapsed :count="impactData.total_unpowered" heading-level="h6" count-class="small">
        <template slot="title">
          {{ __('devices.title_unpowered') }}
        </template>
        <template slot="content">
          <FixometerRecordsTable
              :is-admin="isAdmin"
              :powered="false"
              :clusters="clusters"
              :brands="brands"
              :barrier-list="barrierList"
              :category="category_unpowered"
              :model="model"
              :item_type="item_type"
              :comments="comments"
              :wiki="wiki"
              :status="status"
              :group="group"
              :from_date="from_date"
              :to_date="to_date"
              :total.sync="total_unpowered"
          />
        </template>
      </CollapsibleSection>
    </div>
  </div>
</template>
<script>
import FixometerHeading from './FixometerHeading.vue'
import FixometerGlobalImpact from './FixometerGlobalImpact.vue'
import FixometerRecordsTable from './FixometerRecordsTable.vue'
import FixometerFilters from './FixometerFilters.vue'
import CollapsibleSection from './CollapsibleSection.vue'
import auth from '../mixins/auth'
import AlertBanner from './AlertBanner.vue'

export default {
  components: {
    CollapsibleSection, FixometerFilters, FixometerRecordsTable, FixometerGlobalImpact, FixometerHeading, AlertBanner},
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
    isAdmin: {
      type: Boolean,
      required: true
    },
    userGroups: {
      type: Array,
      required: true,
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

      total_powered: 0,
      total_unpowered: 0,

      startExpandedItems: false,
      startExpandedEvents: false,

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

    this.total_powered = this.impactData.total_powered
    this.total_unpowered = this.impactData.total_unpowered

    this.$store.dispatch('groups/setList', {
      groups: this.userGroups
    })
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
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.iconsmall {
  height: 15px;
  margin-bottom: 5px;
}

.fp-layout {
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