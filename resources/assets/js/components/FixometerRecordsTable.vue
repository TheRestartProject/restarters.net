<template>
  <div>
    <p class="text-brand small pl-3">{{ translatedTableIntro }} TODO</p>
    <div class="pl-3 pr-3">
      <b-table
          :id="'recordstable-' + powered"
          :fields="fields"
          :items="items"
          :per-page="perPage"
          :current-page="currentPage"
          sort-null-last
          thead-tr-class="d-none d-md-table-row">
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
  </div>
</template>
<script>
import { END_OF_LIFE, FIXED, REPAIRABLE } from '../constants'
import moment from 'moment'

const bootaxios = require('axios')

export default {
  props: {
    powered: {
      type: Boolean,
      required: true
    },
    total: {
      type: Number,
      required: true
    }
  },
  data () {
    return {
      currentPage: 1,
      perPage: 5
    }
  },
  computed: {
    fields() {
      let ret = [
        { key: 'device_category.name', label: this.translatedCategory, thClass: 'width20', tdClass: 'width20' }
      ]

      if (this.powered) {
        ret.push({ key: 'model', label: this.translatedModel })
        ret.push({ key: 'brand', label: this.translatedBrand })
      } else {
        ret.push({ key: 'model', label: this.translatedModelOrType })
      }

      ret.push({ key: 'shortProblem', label: this.translatedAssessment, thClass: 'width10', tdClass: 'width10' })
      ret.push({ key: 'device_event.the_group.name', label: this.translatedGroup })
      ret.push({ key: 'repair_status', label: this.translatedStatus, thClass: 'width90px', tdClass: 'width90px' })
      ret.push({ key: 'device_event.event_date', label: this.translatedDevicesDate, thClass: 'width90px', tdClass: 'width90px' })

      return ret
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedBrand() {
      return this.$lang.get('devices.brand')
    },
    translatedModel() {
      return this.$lang.get('devices.model')
    },
    translatedModelOrType() {
      return this.$lang.get('devices.model_or_type')
    },
    translatedAssessment() {
      return this.$lang.get('devices.assessment')
    },
    translatedGroup() {
      return this.$lang.get('devices.group')
    },
    translatedStatus() {
      return this.$lang.get('devices.status')
    },
    translatedDevicesDate() {
      return this.$lang.get('devices.devices_date')
    },
    translatedTableIntro() {
      return this.$lang.get('devices.table_intro')
    },
  },
  methods: {
    items(ctx, callback) {
      console.log("Items called", ctx)
      // Don't use store - we don't need this to be reactive.
      axios.get('/api/devices/' + ctx.currentPage + '/' + ctx.perPage + '/' + this.powered)
        .then(ret => {
          console.log("Returned", ret.data)
          callback(ret.data)
        }).catch(() => {
          callback([])
      })



      // Indicate that callback is being used.
      return null
    },
    status(data) {
      switch (data.item.repair_status) {
        case FIXED: return this.$lang.get('partials.fixed');
        case REPAIRABLE: return this.$lang.get('partials.repairable');
        case END_OF_LIFE: return this.$lang.get('partials.end');
        default: return null
      }
    },
    badgeClass(data) {
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
    formatDate(data) {
      return new moment(data.item.device_event.event_date).format('DD/MM/YYYY')
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
</style>
