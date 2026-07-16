<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DeviceRow from './DeviceRow.vue'
import DeviceForm from './DeviceForm.vue'

// Wires DeviceRow.vue/DeviceForm.vue into the event view page
// (api-contracts-phase-c.md C5; design.md §6.2 C5 task brief). Functional
// spec: resources/js/components/EventDevices.vue + EventDeviceList.vue.
//
// This is a separate component from components/events/EventDevicesReadOnly
// .vue (C3), not that component extended in place: EventDevicesReadOnly is
// pure/presentational (devices/loading/stats in, nothing else) with its own
// green test file that asserts it never shows edit controls: growing it to
// also own DeviceForm's add flow and DeviceRow's per-row edit/delete state
// would have meant rewriting that whole test file for a component that
// would then do two different jobs depending on a prop. Duplicating the
// tab/table/empty-state shell (~40 lines) was the cheaper, lower-risk
// option, and keeps ReadOnly available as a genuinely read-only building
// block (e.g. for a future "public event summary" view with no edit
// affordances at all, logged-out viewers, etc).
//
// party/view/[id].vue uses this component for every viewer (not just
// canedit ones) - passing `canedit: false` renders the identical read-only
// column set ReadOnly does, just via DeviceRow instead.
const props = defineProps({
  eventId: {
    type: Number,
    required: true,
  },
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
  canedit: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()

const activeTab = ref('powered')
const addingPowered = ref(false)
const addingUnpowered = ref(false)

const powered = computed(() => props.devices.filter((d) => d.category?.powered))
const unpowered = computed(() => props.devices.filter((d) => !d.category?.powered))

function round(n) {
  return Math.round(n || 0)
}

function onAdded() {
  addingPowered.value = false
  addingUnpowered.value = false
}
</script>

<template>
  <div data-testid="event-devices">
    <h2>
      {{ t('devices.title_items_at_event') }}
      <span class="fw-normal">({{ devices.length }})</span>
    </h2>

    <!-- Stale-while-revalidate: only swap to the spinner when there is no
         data yet. A forced refresh (e.g. after a photo upload) must NOT
         unmount the table - that destroyed DeviceRow's editing state and
         closed the edit form mid-upload. -->
    <div v-if="loading && devices.length === 0" data-testid="event-devices-loading">
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

      <div v-if="activeTab === 'powered'">
        <div class="table-responsive pt-3">
          <table class="table" data-testid="event-devices-table-powered">
            <thead>
              <tr>
                <th>{{ t('devices.item_type_short') }}</th>
                <th>{{ t('devices.category') }}</th>
                <th>{{ t('devices.brand') }}</th>
                <th>{{ t('devices.age') }}</th>
                <th>{{ t('devices.devices_description') }}</th>
                <th>{{ t('devices.status') }}</th>
                <th>{{ t('devices.spare_parts') }}</th>
                <th v-if="canedit" />
              </tr>
            </thead>
            <tbody>
              <DeviceRow
                v-for="d in powered"
                :key="d.id"
                :device="d"
                :event-id="eventId"
                :powered="true"
                :canedit="canedit"
              />
              <tr v-if="powered.length === 0">
                <td :colspan="canedit ? 8 : 7" class="text-muted" data-testid="event-devices-empty">
                  {{ t('client.events.no_devices') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <BButton
          v-if="canedit"
          variant="primary"
          class="mb-3"
          data-testid="add-powered-device-desktop"
          @click="addingPowered = !addingPowered"
        >
          {{ t('partials.add_device_powered') }}
        </BButton>
        <DeviceForm v-if="addingPowered" :event-id="eventId" :powered="true" @saved="onAdded" @cancel="addingPowered = false" />
      </div>

      <div v-else>
        <div class="table-responsive pt-3">
          <table class="table" data-testid="event-devices-table-unpowered">
            <thead>
              <tr>
                <th>{{ t('devices.item_type_short') }}</th>
                <th>{{ t('devices.category') }}</th>
                <th>{{ t('devices.age') }}</th>
                <th>{{ t('devices.devices_description') }}</th>
                <th>{{ t('devices.status') }}</th>
                <th>{{ t('devices.spare_parts') }}</th>
                <th v-if="canedit" />
              </tr>
            </thead>
            <tbody>
              <DeviceRow
                v-for="d in unpowered"
                :key="d.id"
                :device="d"
                :event-id="eventId"
                :powered="false"
                :canedit="canedit"
              />
              <tr v-if="unpowered.length === 0">
                <td :colspan="canedit ? 7 : 6" class="text-muted" data-testid="event-devices-empty">
                  {{ t('client.events.no_devices') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <BButton
          v-if="canedit"
          variant="primary"
          class="mb-3"
          data-testid="add-unpowered-device-desktop"
          @click="addingUnpowered = !addingUnpowered"
        >
          {{ t('partials.add_device_unpowered') }}
        </BButton>
        <DeviceForm v-if="addingUnpowered" :event-id="eventId" :powered="false" @saved="onAdded" @cancel="addingUnpowered = false" />
      </div>
    </template>
  </div>
</template>
