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
    <td v-if="powered">{{ device.brand }}</td>
    <td>{{ parseFloat(device.age) ? device.age : '-' }}</td>
    <td>{{ device.short_problem }}</td>
    <td>
      <BBadge v-if="statusLabel" :variant="statusVariant" :data-testid="`event-device-status-${device.id}`">
        {{ statusLabel }}
      </BBadge>
    </td>
    <td class="text-center">
      <span v-if="sparePartsNeeded" :data-testid="`event-device-spare-parts-${device.id}`">&#10003;</span>
    </td>
    <td v-if="canedit" class="text-end">
      <div v-if="deleteError" class="small text-danger" :data-testid="`event-device-delete-error-${device.id}`">
        {{ deleteError }}
      </div>
      <template v-if="confirmingDelete">
        <span class="small me-1">{{ t('devices.confirm_delete') }}</span>
        <button
          type="button"
          class="btn btn-sm btn-link text-danger p-0 me-2"
          :disabled="deleting"
          :data-testid="`event-device-delete-confirm-${device.id}`"
          @click="confirmDelete"
        >
          {{ t('partials.yes') }}
        </button>
        <button type="button" class="btn btn-sm btn-link p-0" @click="cancelDelete">
          {{ t('partials.cancel') }}
        </button>
      </template>
      <template v-else>
        <button type="button" class="btn btn-sm btn-link p-0 me-2" :data-testid="`event-device-edit-${device.id}`" @click="edit">
          {{ t('client.devices.edit') }}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-link text-danger p-0"
          :data-testid="`event-device-delete-${device.id}`"
          @click="askDelete"
        >
          {{ t('devices.delete_device') }}
        </button>
      </template>
    </td>
  </tr>
  <tr v-else :data-testid="`event-device-editing-${device.id}`">
    <td :colspan="(powered ? 7 : 6) + (canedit ? 1 : 0)" class="p-0">
      <DeviceForm :event-id="eventId" :device="device" :powered="powered" @saved="onSaved" @cancel="onCancel" />
    </td>
  </tr>
</template>
