<template>
  <div class="records bg-white">
    <div class="pl-md-3 pr-md-3">
      <b-table
          ref="table"
          :id="tableId"
          :fields="fields"
          :items="items"
          :per-page="perPage"
          :current-page="currentPage"
          sort-null-last
      >
        <template slot="cell(item_type)" slot-scope="data">
          <span v-if="data.item.item_type">
            {{ data.item.item_type }}
          </span>
          <em v-else class="text-muted">
            -
          </em>
        </template>
        <template slot="cell(category)" slot-scope="data">
          {{ data.item.category ? __(data.item.category.name) : '' }}
        </template>
        <template slot="cell(short_problem)" slot-scope="data">
          <div v-line-clamp="3">
            {{ data.item.short_problem }}
          </div>
        </template>
        <template slot="cell(brand)" slot-scope="data">
          <span v-if="data.item.brand">
            {{ data.item.brand }}
          </span>
          <em v-else class="text-muted">
            -
          </em>
        </template>
        <template slot="cell(repair_status)" slot-scope="data">
          <div :class="badgeClass(data)">
            {{ showStatus(data) }}
          </div>
        </template>
        <template slot="cell(event_date)" slot-scope="data">
          <a v-if="data.item.eventid" :href="eventUrl(data.item)" class="record-event-link" :title="__('devices.view_event_for_repair')">
            {{ formatDate(data) }}
          </a>
          <span v-else>{{ formatDate(data) }}</span>
        </template>
        <template slot="cell(show_details)" slot-scope="row">
          <div class="text-md-right">
            <button type="button" class="btn btn-link p-0 pl-md-2 pr-2 record-details-toggle" @click="row.toggleDetails" :title="isAdmin ? __('devices.edit_record') : __('devices.view_record')" :aria-label="isAdmin ? __('devices.edit_record') : __('devices.view_record')">
              <b-img v-if="isAdmin" class="icon" :src="imageUrl('/icons/edit_ico_green.svg')" alt="" />
              <b-img v-else class="icon" :src="imageUrl('/icons/info_ico_green.svg')" alt="" />
            </button>
          </div>
        </template>
        <template slot="row-details" slot-scope="row">
          <EventDevice
              :id="row.item.id"
              :powered="Boolean(row.item.category && row.item.category.powered)"
              :add="false"
              :edit="isAdmin"
              :delete-button="true"
              :clusters="clusters"
              :eventid="row.item.eventid"
              :brands="brands"
              :barrier-list="barrierList"
              :cancel-button="false"
              :show-info="false"
              @close="closed(row)" />
        </template>
      </b-table>
      <div class="d-flex justify-content-end" v-if="total > perPage">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            :aria-controls="tableId"
        ></b-pagination>
      </div>
    </div>
  </div>
</template>
<script>
import { END_OF_LIFE, FIXED, REPAIRABLE } from '../constants'
import moment from 'moment'
import Vue from 'vue'
import lineClamp from 'vue-line-clamp'
import EventDevice from './EventDevice.vue'
import images from '../mixins/images'

Vue.use(lineClamp, {
  textOverflow: 'ellipsis'
})

import axios from 'axios'
const bootaxios = axios

export default {
  mixins: [images],
  components: {EventDevice},
  props: {
    isAdmin: {
      type: Boolean,
      required: false,
      default: false
    },
    powered: {
      // Null lists both powered and unpowered items.
      type: Boolean,
      required: false,
      default: null
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
    category: {
      type: Number,
      required: false,
      default: null
    },
    brand: {
      type: String,
      required: false,
      default: null
    },
    model: {
      type: String,
      required: false,
      default: null
    },
    item_type: {
      type: String,
      required: false,
      default: null
    },
    status: {
      // One of the repair status strings, e.g. 'Fixed'.
      type: String,
      required: false,
      default: null
    },
    comments: {
      type: String,
      required: false,
      default: null
    },
    wiki: {
      type: Boolean,
      required: false,
      default: false
    },
    group: {
      type: String,
      required: false,
      default: null
    },
    from_date: {
      type: String,
      required: false,
      default: null
    },
    to_date: {
      type: String,
      required: false,
      default: null
    }
  },
  data () {
    return {
      currentPage: 1,
      perPage: 20,
      showModal: false,
      device: null,
      total: 0,
      co2: 0,
      weight: 0
    }
  },
  computed: {
    tableId() {
      return 'recordstable'
    },
    fields () {
      // The keys of sortable fields are what we ask the server to sort by.
      return [
        {
          key: 'item_type',
          label: this.__('devices.model_or_type'),
          sortable: true,
          tdClass: 'pl-0 pl-md-3'
        },
        {
          key: 'category',
          label: this.__('devices.category'),
          thClass: 'width20 pl-0 pl-md-3',
          tdClass: 'width20 pl-0 pl-md-3',
          sortable: true
        },
        {
          key: 'brand',
          label: this.__('devices.brand'),
          sortable: true,
          thClass: 'd-none d-md-table-cell',
          tdClass: 'd-none d-md-table-cell'
        },
        {
          key: 'short_problem',
          label: this.__('devices.assessment'),
          thClass: 'width10 d-none d-md-table-cell',
          tdClass: 'width10 d-none d-md-table-cell'
        },
        {
          key: 'repair_status',
          label: this.__('devices.status'),
          thClass: 'width90px',
          tdClass: 'width90px',
          sortable: true
        },
        {
          key: 'groupname',
          label: this.__('devices.group'),
          sortable: true,
          thClass: 'd-none d-md-table-cell',
          tdClass: 'd-none d-md-table-cell'
        },
        {
          key: 'event_date',
          label: this.__('devices.devices_date'),
          thClass: 'width90px',
          tdClass: 'width90px',
          sortable: true
        },
        {
          // Bootstrap tables have a mechanism to show a details row.  This is exactly what we need to show the
          // view/edit section for a device.
          key: 'show_details',
          label: '',
        }
      ]
    },
  },
  watch: {
    powered(newVal) {
      this.$refs.table.refresh()
    },
    category(newVal) {
      this.$refs.table.refresh()
    },
    brand(newVal) {
      this.$refs.table.refresh()
    },
    model(newVal) {
      this.$refs.table.refresh()
    },
    comments(newVal) {
      this.$refs.table.refresh()
    },
    wiki(newVal) {
      this.$refs.table.refresh()
    },
    item_type(newVal) {
      this.$refs.table.refresh()
    },
    status(newVal) {
      this.$refs.table.refresh()
    },
    group(newVal) {
      this.$refs.table.refresh()
    },
    from_date(newVal) {
      this.$refs.table.refresh()
    },
    to_date(newVal) {
      this.$refs.table.refresh()
    },
    total(newVal) {
      this.$emit('update:total', newVal)
    },
    weight(newVal) {
      this.$emit('update:weight', newVal)
    },
    co2(newVal) {
      this.$emit('update:co2', newVal)
    }
  },
  methods: {
    items (ctx, callback) {
      // We want to take advantage of the paging and sorting features of the table, and therefore we are using the
      // table's async method of providing data.  The server sorts by the column the table is sorted by; with no
      // column chosen it shows the most recent events first.
      const sortBy = ctx.sortBy || null
      let sortDesc = 'DESC'

      if (ctx.sortBy && !ctx.sortDesc) {
        sortDesc = 'ASC'
      }

      axios.get('/api/devices/' + ctx.currentPage + '/' + ctx.perPage, {
        params: {
          sortBy: sortBy,
          sortDesc: sortDesc,
          powered: this.powered === null ? undefined : this.powered,
          category: this.category,
          brand: this.brand,
          model: this.model,
          item_type: this.item_type,
          status: this.status,
          comments: this.comments,
          wiki: this.wiki,
          group: this.group,
          from_date: this.from_date,
          to_date: this.to_date,
          _showDetails: true
        }
      })
          .then(ret => {
            this.total = ret.data.count
            this.weight = ret.data.weight
            this.co2 = ret.data.co2

            this.$nextTick(async () => {
              // Update the store to contain (just) the items we have returned.  They need to be in the store for
              // other components (e.g. EventDevice) to work correctly.
              //
              // We do this in nextTick because the tables component doesn't support async/await.
              await this.$store.dispatch('devices/clear')

              ret.data.items.forEach(item => {
                this.$store.dispatch('devices/setForEvent', {
                  eventid: item.eventid,
                  devices: [ item ]
                })
              })
            })

            callback(ret.data.items)
          }).catch(() => {
        callback([])
      })

      // Indicate that callback is being used.
      return null
    },
    showStatus (data) {
      switch (data.item.repair_status) {
        case FIXED:
          return this.__('partials.fixed')
        case REPAIRABLE:
          return this.__('partials.repairable')
        case END_OF_LIFE:
          return this.__('partials.end')
        default:
          return null
      }
    },
    badgeClass (data) {
      switch (data.item.repair_status) {
        case FIXED:
          return 'badge badge-success'
        case REPAIRABLE:
          return 'badge badge-warning'
        case END_OF_LIFE:
          return 'badge badge-danger'
        default:
          return null
      }
    },
    eventUrl (item) {
      // Straight to the items on the event's page.
      return '/party/view/' + item.eventid + '#devices-section'
    },
    formatDate (data) {
      return data.item.event_date ? new moment(data.item.event_date).format('DD/MM/YYYY') : ''
    },
    closed(row) {
      // We have saved/edited the device.  We want to refresh the table to any edited data is updated, and
      // close the details.
      this.$root.$emit('bv::refresh::table', this.tableId)

      row.toggleDetails()
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.badge {
  width: 90px;
  padding: 0;
  border-radius: 0;
  font-size: small;
  line-height: 2;
  text-transform: uppercase;
}

::v-deep .width10 {
  width: 10%;
}

::v-deep .width20 {
  width: 20%;
}

::v-deep .width90px {
  width: 90px;
}

::v-deep .table th {
  padding: 5px;
}


@include media-breakpoint-down(sm) {
  ::v-deep .table {
    tr {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-bottom: 1px solid black;
      padding-bottom: 5px;
    }

    th, td {
      width: 100%;
      border-bottom: none !important;
      padding-left: 0px;
      padding-bottom: 0px;
    }

    th {
      padding-top: 5px;
      padding-bottom: 5px;
    }
  }
}

::v-deep tr.b-table-details td {
  padding: 0px;
}

</style>