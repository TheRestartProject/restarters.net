<template>
  <div>
    <b-table-simple responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
      <b-thead>
        <b-tr>
          <b-th>
            {{ translatedCategory }}
          </b-th>
          <b-th v-if="powered">
            {{ translatedModel }}
          </b-th>
          <b-th v-if="powered" class="d-none d-md-table-cell">
            {{ translatedBrand }}
          </b-th>
          <b-th v-if="!powered">
            {{ translatedModelOrType }}
          </b-th>
          <b-td v-if="!powered" class="d-table-cell d-md-none" />
          <b-th class="d-none d-md-table-cell">
            {{ translatedAge }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ translatedDescription }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ translatedStatus }}
          </b-th>
          <b-th class="d-none d-md-table-cell">
            {{ translatedSpareParts }}
          </b-th>
          <b-th v-if="canedit" class="d-none d-md-table-cell">
          </b-th>
        </b-tr>
      </b-thead>
      <b-tbody class="borders">
        <EventDeviceSummary v-for="device in devices" :key="'device-' + device.iddevices" :device="device" :canedit="canedit" :powered="powered" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
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
    powered: {
      type: Boolean,
      required: true
    }
  },
  computed: {
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
    translatedAge() {
      return this.$lang.get('devices.age')
    },
    translatedDescription() {
      return this.$lang.get('devices.devices_description')
    },
    translatedStatus() {
      return this.$lang.get('devices.status')
    },
    translatedSpareParts() {
      return this.$lang.get('devices.spare_parts')
    },
  }
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