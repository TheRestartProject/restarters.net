<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { deviceStatusKey, deviceStatusVariant, deviceSparePartsNeeded } from '../../composables/useDeviceDisplay.js'
import DeviceForm from './DeviceForm.vue'

// One device's summary row, with inline edit/delete for canedit viewers -
// api-contracts-phase-c.md C5. Functional spec: EventDeviceSummary.vue.
// Column set/order matches components/events/EventDevicesReadOnly.vue (C3)
// exactly, plus a trailing edit/delete column when `canedit` - the two
// components are meant to render identical rows for the fields they share,
// see components/devices/EventDevicesPanel.vue's doc comment for why they
// aren't the same component.
const props = defineProps({
  device: {
    type: Object,
    required: true,
  },
  eventId: {
    type: Number,
    required: true,
  },
  powered: {
    type: Boolean,
    required: true,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const devicesStore = useDevicesStore()

const editing = ref(false)
const confirmingDelete = ref(false)
const deleting = ref(false)
const deleteError = ref('')

const statusLabel = computed(() => {
  const key = deviceStatusKey(props.device)
  return key ? t(key) : null
})
const statusVariant = computed(() => deviceStatusVariant(props.device))
const sparePartsNeeded = computed(() => deviceSparePartsNeeded(props.device))

function edit() {
  editing.value = true
}

function onSaved() {
  editing.value = false
}

function onCancel() {
  editing.value = false
}

function askDelete() {
  confirmingDelete.value = true
}

function cancelDelete() {
  confirmingDelete.value = false
}

async function confirmDelete() {
  confirmingDelete.value = false
  deleting.value = true
  deleteError.value = ''

  try {
    await devicesStore.deleteDevice(props.eventId, props.device.id)
  } catch {
    deleteError.value = t('client.devices.delete_failed')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <tr v-if="!editing" :data-testid="`event-device-${device.id}`">
    <td>{{ device.item_type || '-' }}</td>
    <td>{{ device.category ? t(device.category.name) : '-' }}</td>
    <!-- Gap fix (MEDIUM): legacy hides Brand/Age/Assessment/Status/Spare-parts
         below md (EventDeviceList.vue's `d-none d-md-table-cell` fields). -->
    <td v-if="powered" class="d-none d-md-table-cell">{{ device.brand }}</td>
    <td class="d-none d-md-table-cell">{{ parseFloat(device.age) ? device.age : '-' }}</td>
    <td class="d-none d-md-table-cell">{{ device.short_problem }}</td>
    <td class="d-none d-md-table-cell">
      <BBadge v-if="statusLabel" :variant="statusVariant" :data-testid="`event-device-status-${device.id}`">
        {{ statusLabel }}
      </BBadge>
    </td>
    <td class="text-center d-none d-md-table-cell">
      <span v-if="sparePartsNeeded" :data-testid="`event-device-spare-parts-${device.id}`">&#10003;</span>
    </td>
    <td v-if="canedit" class="text-end">
      <!-- Gap fix (MEDIUM): legacy uses small icon buttons
           (edit_ico_green.svg/delete_ico_red.svg), not text links. -->
      <button type="button" class="device-row-icon-btn me-2" :data-testid="`event-device-edit-${device.id}`" @click="edit">
        <img src="/icons/edit_ico_green.svg" class="device-row-icon-btn__icon" alt="">
        <span class="visually-hidden">{{ t('client.devices.edit') }}</span>
      </button>
      <button
        type="button"
        class="device-row-icon-btn"
        :data-testid="`event-device-delete-${device.id}`"
        @click="askDelete"
      >
        <img src="/icons/delete_ico_red.svg" class="device-row-icon-btn__icon" alt="">
        <span class="visually-hidden">{{ t('devices.delete_device') }}</span>
      </button>
      <div v-if="deleteError" class="small text-danger" :data-testid="`event-device-delete-error-${device.id}`">
        {{ deleteError }}
      </div>
    </td>
  </tr>
  <tr v-else class="device-row-editing" :data-testid="`event-device-editing-${device.id}`">
    <td :colspan="(powered ? 7 : 6) + (canedit ? 1 : 0)" class="p-0">
      <DeviceForm :event-id="eventId" :device="device" :powered="powered" @saved="onSaved" @cancel="onCancel" />
    </td>
  </tr>

  <!-- Gap fix (MEDIUM): a modal confirm dialog (matching legacy's
       ConfirmModal), not an inline row-replacement Yes/Cancel swap. -->
  <BModal
    v-if="canedit"
    :model-value="confirmingDelete"
    :title="t('devices.delete_device')"
    no-footer
    :data-testid="`event-device-delete-modal-${device.id}`"
    @hide="cancelDelete"
  >
    <p>{{ t('devices.confirm_delete') }}</p>
    <div class="d-flex justify-content-end gap-2">
      <BButton variant="outline-secondary" @click="cancelDelete">
        {{ t('partials.cancel') }}
      </BButton>
      <BButton
        variant="danger"
        :disabled="deleting"
        :data-testid="`event-device-delete-confirm-${device.id}`"
        @click="confirmDelete"
      >
        {{ t('devices.delete_device') }}
      </BButton>
    </div>
  </BModal>
</template>

<style scoped>
.device-row-icon-btn {
  display: inline-flex;
  padding: 0;
  border: 0;
  background: none;
  line-height: 1;
  cursor: pointer;
}

.device-row-icon-btn__icon {
  width: 1.1rem;
}

/* Gap fix (invented-styling finding): EventDeviceSummary.vue's own
   `.badge` override verbatim - a flat, uppercase, fixed-width badge rather
   than BBadge's default rounded pill. */
.badge {
  width: 90px;
  padding: 0;
  border-radius: 0;
  font-size: small;
  line-height: 2;
  text-transform: uppercase;
}
</style>
