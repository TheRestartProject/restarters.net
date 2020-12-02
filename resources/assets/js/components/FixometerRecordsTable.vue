<template>
  <div>
    <p class="text-brand small pl-3">{{ translatedTableIntro }}</p>
    <div class="pl-md-3 pr-md-3">
      <b-table
          ref="table"
          :id="'recordstable-' + powered"
          :fields="fields"
          :items="items"
          :per-page="perPage"
          :current-page="currentPage"
          sort-null-last
          tbody-tr-class="clickme"
          @row-clicked="clicked"
      >
        <template slot="cell(repair_status)" slot-scope="data">
          <div :class="badgeClass(data)">
            {{ status(data) }}
          </div>
        </template>
        <template slot="cell(device_event.event_date)" slot-scope="data">
          {{ formatDate(data) }}
        </template>
      </b-table>
      <div class="d-flex justify-content-end" v-if="total > perPage">
        <b-pagination
            v-model="currentPage"
            :total-rows="total"
            :per-page="perPage"
            :aria-controls="'recordstable-' + powered"
        ></b-pagination>
      </div>
    </div>
    <DeviceModal ref="modal" :device="device" v-if="device" />
  </div>
</template>
<script>
import { END_OF_LIFE, FIXED, REPAIRABLE } from '../constants'
import moment from 'moment'
import DeviceModel from './DeviceModel'
import DeviceModal from './DeviceModal'

const bootaxios = require('axios')

export default {
  components: {DeviceModal, DeviceModel},
  props: {
    powered: {
      type: Boolean,
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
    category: {
      type: Number,
      required: false,
      default: null
    }
  },
  data () {
    return {
      currentPage: 1,
      perPage: 5,
      showModal: false,
      device: null,
      total: 0
    }
  },
  computed: {
    fields () {
      let ret = [
        {
          key: 'device_category.name',
          label: this.translatedCategory,
          thClass: 'width20 pl-0 pl-md-3',
          tdClass: 'width20 pl-0 pl-md-3',
          sortable: true
        }
      ]

      if (this.powered) {
        ret.push({key: 'model', label: this.translatedModel, sortable: true})
        ret.push({key: 'brand', label: this.translatedBrand, sortable: true, thClass: 'd-none d-md-table-cell', tdClass: 'd-none d-md-table-cell'})
      } else {
        ret.push({key: 'model', label: this.translatedModelOrType, sortable: true, tdClass: 'pl-0 pl-md-3'})
      }

      ret.push({key: 'shortProblem', label: this.translatedAssessment, thClass: 'width10 d-none d-md-table-cell', tdClass: 'width10 d-none d-md-table-cell'})
      ret.push({key: 'device_event.the_group.name', label: this.translatedGroup, sortable: true, thClass: 'd-none d-md-table-cell', tdClass: 'd-none d-md-table-cell'})
      ret.push({
        key: 'repair_status',
        label: this.translatedStatus,
        thClass: 'width90px',
        tdClass: 'width90px',
        sortable: true
      })
      ret.push({
        key: 'device_event.event_date',
        label: this.translatedDevicesDate,
        thClass: 'width90px',
        tdClass: 'width90px',
        sortable: true
      })

      return ret
    },
    translatedCategory () {
      return this.$lang.get('devices.category')
    },
    translatedBrand () {
      return this.$lang.get('devices.brand')
    },
    translatedModel () {
      return this.$lang.get('devices.model')
    },
    translatedModelOrType () {
      return this.$lang.get('devices.model_or_type')
    },
    translatedAssessment () {
      return this.$lang.get('devices.assessment')
    },
    translatedGroup () {
      return this.$lang.get('devices.group')
    },
    translatedStatus () {
      return this.$lang.get('devices.status')
    },
    translatedDevicesDate () {
      return this.$lang.get('devices.devices_date')
    },
    translatedTableIntro () {
      return this.$lang.get('devices.table_intro')
    },
    translatedClose() {
      return this.$lang.get('partials.close')
    },
  },
  watch: {
    category(newVal) {
      console.log("Category changed to", newVal)
      this.$refs.table.refresh()
    }
  },
  methods: {
    items (ctx, callback) {
      // Don't use store - we don't need this to be reactive.
      // Default sort is descending date order.
      // The table will provide a full name in sortBy - we just want the last part.
      let sortBy = 'event_date'
      let sortDesc = ctx.sortBy ? (ctx.sortDesc ? 'DESC' : 'ASC') : 'DESC'

      if (ctx.sortBy) {
        // We have to munge what the table gives us a bit to match what the server can query.
        console.log('Ctx', ctx.sortBy)
        sortBy = ctx.sortBy
            .replace('device_event.the_group.', 'groups.')
            .replace('device_event.', 'events.')
            .replace('device_category.', 'categories.')
      }

      axios.get('/api/devices/' + ctx.currentPage + '/' + ctx.perPage, {
        params: {
          sortBy: sortBy,
          sortDesc: sortDesc,
          powered: this.powered,
          category: this.category
        }
      })
          .then(ret => {
            this.total = ret.data.count
            callback(ret.data.items)
          }).catch(() => {
        callback([])
      })

      // Indicate that callback is being used.
      return null
    },
    status (data) {
      switch (data.item.repair_status) {
        case FIXED:
          return this.$lang.get('partials.fixed')
        case REPAIRABLE:
          return this.$lang.get('partials.repairable')
        case END_OF_LIFE:
          return this.$lang.get('partials.end')
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
    formatDate (data) {
      return new moment(data.item.device_event.event_date).format('DD/MM/YYYY')
    },
    clicked (device) {
      this.device = device
      this.$nextTick(() => {
        this.$refs.modal.show()
      })
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.badge {
  width: 90px;
  padding: 0;
  border-radius: 0;
  font-size: small;
  line-height: 2;
  text-transform: uppercase;
}

/deep/ .width10 {
  width: 10%;
}

/deep/ .width20 {
  width: 20%;
}

/deep/ .width90px {
  width: 90px;
}

/deep/ .table th {
  padding: 5px;
}
</style>