<template>
  <div class="device-list">
    <b-table-simple responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
      <b-thead>
        <b-tr>
          <b-th>
            {{ __('devices.item_type_short') }}
          </b-th>
          <b-th>
            {{ __('devices.category') }}
          </b-th>
          <b-th v-if="powered" class="d-none d-md-table-cell">
              {{ __('devices.brand') }}
          </b-th>
          <b-td v-if="!powered" class="d-table-cell d-md-none" />
          <b-th class="d-none d-md-table-cell">
            {{ __('devices.age') }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ __('devices.devices_description') }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ __('devices.status') }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ __('devices.spare_parts') }}
          </b-th>
          <b-th v-if="canedit">
          </b-th>
        </b-tr>
      </b-thead>
      <b-tbody class="borders">
        <EventDeviceSummary v-for="device in devices" :key="'device-' + device.id" :id="device.id" :canedit="canedit" :powered="powered" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
      </b-tbody>
    </b-table-simple>
  </div>
</template>
<script>
import event from '../mixins/event'
import EventDeviceSummary from './EventDeviceSummary'

export default {
  components: {EventDeviceSummary},
  mixins: [ event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    powered: {
      type: Boolean,
      required: true
    },
    devices: {
      type: Array,
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
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.borders {
  border-bottom: 2px solid black;
}
</style>
