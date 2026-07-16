<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { deviceStatusKey, deviceStatusVariant, deviceSparePartsNeeded } from '../../composables/useDeviceDisplay.js'

// GET /api/v2/events/{id}/devices (api-contracts-phase-c.md C1e). Read-only
// port of EventDevices.vue/EventDeviceList.vue/EventDeviceSummary.vue's
// display fields. Stays deliberately read-only-only (used as-is when the
// viewer can't edit); components/devices/EventDevicesPanel.vue (C5) is the
// separate, edit-capable sibling built on top of DeviceRow.vue/
// DeviceForm.vue for canedit viewers - see its file doc comment for why
// this component wasn't extended in place instead.
const props = defineProps({
  devices: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  stats: {
    type: Object,
    default: null,
  },
})

const { t } = useI18n()

const activeTab = ref('powered')

const powered = computed(() => props.devices.filter((d) => d.category?.powered))
const unpowered = computed(() => props.devices.filter((d) => !d.category?.powered))

function statusLabel(device) {
  const key = deviceStatusKey(device)
  return key ? t(key) : null
}

function statusVariant(device) {
  return deviceStatusVariant(device)
}

function sparePartsNeeded(device) {
  return deviceSparePartsNeeded(device)
}

function round(n) {
  return Math.round(n || 0)
}
</script>

<template>
  <div data-testid="event-devices">
    <h2>
      {{ t('devices.title_items_at_event') }}
      <span class="fw-normal">({{ devices.length }})</span>
    </h2>

    <div v-if="loading" data-testid="event-devices-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <template v-else>
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'powered' }"
            data-testid="event-devices-tab-powered"
            @click="activeTab = 'powered'"
          >
            <b>{{ t('devices.title_powered') }}</b> ({{ powered.length }})
            <template v-if="stats">
              &middot; {{ round(stats.waste_powered) }}kg &middot; {{ round(stats.co2_powered) }}kg CO2
            </template>
          </button>
        </li>
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'unpowered' }"
            data-testid="event-devices-tab-unpowered"
            @click="activeTab = 'unpowered'"
          >
            <b>{{ t('devices.title_unpowered') }}</b> ({{ unpowered.length }})
            <template v-if="stats">
              &middot; {{ round(stats.waste_unpowered) }}kg &middot; {{ round(stats.co2_unpowered) }}kg CO2
            </template>
          </button>
        </li>
      </ul>

      <div class="table-responsive pt-3">
        <table class="table" :data-testid="`event-devices-table-${activeTab}`">
          <thead>
            <tr>
              <th>{{ t('devices.item_type_short') }}</th>
              <th>{{ t('devices.category') }}</th>
              <th v-if="activeTab === 'powered'">{{ t('devices.brand') }}</th>
              <th>{{ t('devices.age') }}</th>
              <th>{{ t('devices.devices_description') }}</th>
              <th>{{ t('devices.status') }}</th>
              <th>{{ t('devices.spare_parts') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="device in (activeTab === 'powered' ? powered : unpowered)"
              :key="device.id"
              :data-testid="`event-device-${device.id}`"
            >
              <td>{{ device.item_type || '-' }}</td>
              <td>{{ device.category ? t(device.category.name) : '-' }}</td>
              <td v-if="activeTab === 'powered'">{{ device.brand }}</td>
              <td>{{ parseFloat(device.age) ? device.age : '-' }}</td>
              <td>{{ device.short_problem }}</td>
              <td>
                <BBadge v-if="statusLabel(device)" :variant="statusVariant(device)" :data-testid="`event-device-status-${device.id}`">
                  {{ statusLabel(device) }}
                </BBadge>
              </td>
              <td>
                <span v-if="sparePartsNeeded(device)" :data-testid="`event-device-spare-parts-${device.id}`">&#10003;</span>
              </td>
            </tr>
            <tr v-if="(activeTab === 'powered' ? powered : unpowered).length === 0">
              <td colspan="7" class="text-muted" data-testid="event-devices-empty">
                {{ t('client.events.no_devices') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
