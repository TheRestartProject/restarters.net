<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DeviceRow from './DeviceRow.vue'
import DeviceForm from './DeviceForm.vue'

// One powered/unpowered table + add button + add form, factored out of
// EventDevicesPanel.vue so it can be rendered twice - once inside the
// desktop tabs (one table visible at a time) and once per mobile
// collapsible (both tables potentially visible at once, gap 23) - without
// quadrupling the table markup across the two categories x two layouts.
// Column set/testids match EventDeviceList.vue exactly except for the
// `variant` suffix, which keeps the desktop instance's existing testids
// unchanged (add-powered-device-desktop etc.) while giving the new mobile
// instance its own (EventDevices.vue's own add-powered-device-mobile
// class naming precedent).
const props = defineProps({
  devices: {
    type: Array,
    default: () => [],
  },
  powered: {
    type: Boolean,
    required: true,
  },
  eventId: {
    type: Number,
    required: true,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  adding: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'desktop',
    validator: (v) => v === 'desktop' || v === 'mobile',
  },
})

const emit = defineEmits(['toggle-add', 'saved'])

const { t } = useI18n()

const categoryKey = computed(() => (props.powered ? 'powered' : 'unpowered'))
const tableTestId = computed(() => `event-devices-table-${categoryKey.value}${props.variant === 'mobile' ? '-mobile' : ''}`)
const addTestId = computed(() => `add-${categoryKey.value}-device-${props.variant}`)
const emptyTestId = computed(() => `event-devices-empty${props.variant === 'mobile' ? `-mobile-${categoryKey.value}` : ''}`)
const colspan = computed(() => {
  // Powered has one extra (Brand) column.
  const base = props.powered ? 7 : 6
  return props.canedit ? base + 1 : base
})
</script>

<template>
  <div>
    <div class="table-responsive pt-3">
      <table class="table event-devices-table" :data-testid="tableTestId">
        <thead>
          <tr>
            <th>{{ t('devices.item_type_short') }}</th>
            <th>{{ t('devices.category') }}</th>
            <th v-if="powered" class="d-none d-md-table-cell">{{ t('devices.brand') }}</th>
            <th class="d-none d-md-table-cell">{{ t('devices.age') }}</th>
            <th class="d-none d-md-table-cell">{{ t('devices.devices_description') }}</th>
            <th class="d-none d-md-table-cell">{{ t('devices.status') }}</th>
            <th class="d-none d-md-table-cell">{{ t('devices.spare_parts') }}</th>
            <th v-if="canedit" />
          </tr>
        </thead>
        <tbody>
          <DeviceRow
            v-for="d in devices"
            :key="d.id"
            :device="d"
            :event-id="eventId"
            :powered="powered"
            :canedit="canedit"
          />
          <tr v-if="devices.length === 0">
            <td :colspan="colspan" class="text-muted" :data-testid="emptyTestId">
              {{ t('client.events.no_devices') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BButton v-if="canedit" variant="primary" class="mb-3" :data-testid="addTestId" @click="emit('toggle-add')">
      <img :src="'/images/add-icon.svg'" alt="" class="add-device-icon">
      {{ powered ? t('partials.add_device_powered') : t('partials.add_device_unpowered') }}
    </BButton>
    <DeviceForm v-if="adding" :event-id="eventId" :powered="powered" @saved="emit('saved')" @cancel="emit('toggle-add')" />
  </div>
</template>

<style scoped>
/* Gap fix (dropped-behaviour finding): EventDevices.vue's own
   `b-img class="icon mb-1"` sizing for the add-device button icon. */
.add-device-icon {
  width: 20px;
  margin-bottom: 3px;
}
</style>
