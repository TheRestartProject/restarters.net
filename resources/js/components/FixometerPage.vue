<template>
  <div class="mb-2">
    <AlertBanner />
    <FixometerHeading />
    <FixometerGlobalImpact :latest-data="latestData" :impact-data="impactData" class="mt-4" />
    <hr class="mt-md-50 hr-dashed">

    <div class="d-flex justify-content-between">
      <h2 class>
        {{ __('devices.repair_records') }}
        <span class="records-total">({{ total.toLocaleString() }})</span>
      </h2>
      <div>
        <b-btn variant="primary" href="/export/devices" class="export-devices">
          {{ __('devices.export_device_data') }}
        </b-btn>
      </div>
    </div>
    <p>{{ __('devices.search_text') }}</p>
    <div class="fp-layout">
      <FixometerFilters
          :clusters="clusters"
          :brands="brands"
          :powered.sync="powered"
          :category.sync="category"
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
      <FixometerRecordsTable
          :is-admin="isAdmin"
          :powered="powered"
          :clusters="clusters"
          :brands="brands"
          :barrier-list="barrierList"
          :category="category"
          :brand="brand"
          :model="model"
          :item_type="item_type"
          :comments="comments"
          :wiki="wiki"
          :status="status"
          :group="group"
          :from_date="from_date"
          :to_date="to_date"
          :total.sync="total"
      />
    </div>
  </div>
</template>
<script>
import FixometerHeading from './FixometerHeading.vue'
import FixometerGlobalImpact from './FixometerGlobalImpact.vue'
import FixometerRecordsTable from './FixometerRecordsTable.vue'
import FixometerFilters from './FixometerFilters.vue'
import auth from '../mixins/auth'
import AlertBanner from './AlertBanner.vue'

export default {
  components: {
    FixometerFilters, FixometerRecordsTable, FixometerGlobalImpact, FixometerHeading, AlertBanner},
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
      powered: null,
      category: null,
      status: null,
      brand: null,
      model: null,
      item_type: null,
      comments: null,
      wiki: null,
      group: null,
      from_date: null,
      to_date: null,

      total: 0,

      startExpandedItems: false,
      startExpandedEvents: false,

    }
  },
  created() {
    // Apply any URL paramters.
    //
    // We have to list each of these individually for reactivity to notice them.
    const params = (new URL(document.location)).searchParams

    if (params.has('powered')) {
      this.powered = params.get('powered') === 'true'
      this.startExpandedItems = true
    }

    if (params.has('category')) {
      this.category = parseInt(params.get('category'))
      this.startExpandedItems = true
    }

    // Older links name the category separately for powered and unpowered items.
    if (params.has('category_powered')) {
      this.powered = true
      this.category = parseInt(params.get('category_powered'))
      this.startExpandedItems = true
    }

    if (params.has('category_unpowered')) {
      this.powered = false
      this.category = parseInt(params.get('category_unpowered'))
      this.startExpandedItems = true
    }

    if (params.has('status')) {
      this.status = params.get('status')
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

    this.total = (this.impactData.total_powered || 0) + (this.impactData.total_unpowered || 0)

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

      if (this.powered !== null) {
        ret += 'powered=' + encodeURIComponent(this.powered) + '&'
      }

      if (this.category) {
        ret += 'category=' + encodeURIComponent(this.category) + '&'
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