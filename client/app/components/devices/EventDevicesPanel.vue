<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import EventDeviceTable from './EventDeviceTable.vue'
import CollapsibleSection from '../CollapsibleSection.vue'

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

// Gap fix (HIGH): legacy wraps the whole "Items at this event" block (both
// tabs, both tables, add buttons) in one top-level <CollapsibleSection
// collapsed> (EventDevices.vue) - collapsed by default on mobile (desktop
// is always expanded, matching CollapsibleSection.vue's own `collapsedOnMobile`
// semantics - see its doc comment). A prior version rendered a bare,
// always-expanded heading with no collapse behaviour at all.
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
  <CollapsibleSection id="devices-section" collapsed-on-mobile data-testid="event-devices">
    <template #title>
      <h2 class="mb-0 d-flex align-items-center">
        <!-- Gap 23: EventDevices.vue pairs the heading with a TV icon
             (desktop only - `d-none d-md-block` on the b-img itself). -->
        <img :src="'/images/tv.svg'" alt="" class="devices-title-icon d-none d-md-block me-2">
        {{ t('devices.title_items_at_event') }}
        <span class="fw-normal">({{ devices.length }})</span>
      </h2>
    </template>

    <!-- Stale-while-revalidate: only swap to the spinner when there is no
         data yet. A forced refresh (e.g. after a photo upload) must NOT
         unmount the table - that destroyed DeviceRow's editing state and
         closed the edit form mid-upload. -->
    <div v-if="loading && devices.length === 0" data-testid="event-devices-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <!-- Gap 23: EventDevices.vue's content is desktop tabs (one table
         visible at a time) vs mobile two independent, individually-
         collapsible sections (both potentially visible at once) - this was
         a single CSS-grid reflow of the tabbed layout for every viewport,
         losing the mobile two-collapsible structure. -->
    <template v-else>
      <div class="d-none d-md-block" data-testid="event-devices-desktop">
        <ul class="nav nav-tabs">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeTab === 'powered' }"
              data-testid="event-devices-tab-powered"
              @click="activeTab = 'powered'"
            >
              <div class="d-flex justify-content-between align-items-center">
                <div><b>{{ t('devices.title_powered') }}</b> ({{ powered.length }})</div>
                <!-- Gap fix (MEDIUM): legacy pairs each waste/CO2 figure with
                     its own icon (trash_brand.svg / co2_brand.svg) in the tab
                     title, rather than plain inline text. -->
                <div v-if="stats" class="d-flex align-items-center event-devices-tab-impact">
                  <span class="me-2">
                    <img :src="'/images/trash_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                    {{ round(stats.waste_powered) }} kg
                  </span>
                  <span>
                    <img :src="'/images/co2_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                    {{ round(stats.co2_powered) }} kg
                  </span>
                </div>
              </div>
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
              <div class="d-flex justify-content-between align-items-center">
                <div><b>{{ t('devices.title_unpowered') }}</b> ({{ unpowered.length }})</div>
                <div v-if="stats" class="d-flex align-items-center event-devices-tab-impact">
                  <span class="me-2">
                    <img :src="'/images/trash_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                    {{ round(stats.waste_unpowered) }} kg
                  </span>
                  <span>
                    <img :src="'/images/co2_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                    {{ round(stats.co2_unpowered) }} kg
                  </span>
                </div>
              </div>
            </button>
          </li>
        </ul>

        <!-- Gap D5: same explanatory copy DevicesSearchTable.vue shows under
             its powered/unpowered toggle, reusing the fixometer's existing
             devices.description_powered/description_unpowered lang keys
             (contain a <b> tag, hence v-html). -->
        <p class="text-brand small" data-testid="event-devices-description">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="activeTab === 'powered' ? t('devices.description_powered') : t('devices.description_unpowered')" />
        </p>

        <!-- Gap fix (MEDIUM): legacy hides Brand/Age/Assessment/Status/
             Spare-parts below md (EventDeviceList.vue's `d-none
             d-md-table-cell` fields) instead of scrolling a full-width
             table on mobile. -->
        <EventDeviceTable
          v-if="activeTab === 'powered'"
          :devices="powered"
          powered
          :event-id="eventId"
          :canedit="canedit"
          :adding="addingPowered"
          @toggle-add="addingPowered = !addingPowered"
          @saved="onAdded"
        />
        <EventDeviceTable
          v-else
          :devices="unpowered"
          :powered="false"
          :event-id="eventId"
          :canedit="canedit"
          :adding="addingUnpowered"
          @toggle-add="addingUnpowered = !addingUnpowered"
          @saved="onAdded"
        />
      </div>

      <div class="d-block d-md-none" data-testid="event-devices-mobile">
        <CollapsibleSection collapsed-on-mobile data-testid="event-devices-powered-mobile">
          <template #title>
            <div class="d-flex justify-content-between align-items-center small">
              <div><b>{{ t('devices.title_powered') }}</b></div>
              <div v-if="stats" class="d-flex align-items-center event-devices-tab-impact ms-2">
                <span class="me-2">
                  <img :src="'/images/trash_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                  {{ round(stats.waste_powered) }}
                </span>
                <span>
                  <img :src="'/images/co2_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                  {{ round(stats.co2_powered) }}
                </span>
              </div>
            </div>
          </template>
          <p class="text-brand small" data-testid="event-devices-description-powered-mobile">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="t('devices.description_powered')" />
          </p>
          <EventDeviceTable
            :devices="powered"
            powered
            :event-id="eventId"
            :canedit="canedit"
            :adding="addingPowered"
            variant="mobile"
            @toggle-add="addingPowered = !addingPowered"
            @saved="onAdded"
          />
        </CollapsibleSection>

        <CollapsibleSection collapsed-on-mobile data-testid="event-devices-unpowered-mobile">
          <template #title>
            <div class="d-flex justify-content-between align-items-center small">
              <div><b>{{ t('devices.title_unpowered') }}</b></div>
              <div v-if="stats" class="d-flex align-items-center event-devices-tab-impact ms-2">
                <span class="me-2">
                  <img :src="'/images/trash_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                  {{ round(stats.waste_unpowered) }}
                </span>
                <span>
                  <img :src="'/images/co2_brand.svg'" class="event-devices-tab-impact__icon" alt="">
                  {{ round(stats.co2_unpowered) }}
                </span>
              </div>
            </div>
          </template>
          <p class="text-brand small" data-testid="event-devices-description-unpowered-mobile">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="t('devices.description_unpowered')" />
          </p>
          <EventDeviceTable
            :devices="unpowered"
            :powered="false"
            :event-id="eventId"
            :canedit="canedit"
            :adding="addingUnpowered"
            variant="mobile"
            @toggle-add="addingUnpowered = !addingUnpowered"
            @saved="onAdded"
          />
        </CollapsibleSection>
      </div>
    </template>
  </CollapsibleSection>
</template>

<style scoped lang="scss">
.devices-title-icon {
  width: 20px;
}

.event-devices-tab-impact {
  // Lowercased, like the figures elsewhere on this panel (legacy's `.lower`
  // class in EventDevices.vue).
  text-transform: lowercase;
  font-size: 0.85rem;
}

.event-devices-tab-impact__icon {
  width: 1rem;
  margin-right: 0.15rem;
  margin-bottom: 0.15rem;
}

// Gap fix (MEDIUM): compact mobile card layout, matching
// FixometerRecordsTable.vue's equivalent treatment for the fixometer/
// device-search table - below md, hidden columns (d-none d-md-table-cell
// above, and on DeviceRow.vue's own cells) drop out and the remaining
// cells reflow into a 2-column grid. :deep() is needed to reach
// DeviceRow.vue's <tr>/<td> - a separate SFC rendered inside this table.
// DeviceRow's own edit-swap row (.device-row-editing, a single wide <td>
// holding DeviceForm) is excluded, same as DevicesSearchTable.vue excludes
// its own .device-search-details row.
@media (max-width: 767.98px) {
  .event-devices-table :deep(tbody tr:not(.device-row-editing)) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid #222;
  }

  .event-devices-table :deep(tbody tr:not(.device-row-editing) > td) {
    width: 100%;
    border-bottom: none !important;
    padding: 0.2rem 0;
  }
}
</style>
