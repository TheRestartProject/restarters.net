// Shared display mapping for a Device resource's repair_status/spare_parts
// enum strings (Device.php's REPAIR_STATUS_*_STR/SPARE_PARTS_*_STR values),
// used by both components/events/EventDevicesReadOnly.vue (C3) and
// components/devices/DeviceRow.vue (C5) so the two don't drift.
//
// resources/js/components/EventDeviceSummary.vue is the functional spec:
// the status badge label ('End of life' -> "End") deliberately differs
// from the repair-status *select option* label used in the edit form
// ('End of life' -> "End-of-life", devices/DeviceForm.vue via
// partials.end_of_life) - two different legacy translation keys for the
// same enum value, both preserved as-is.
const STATUS_KEYS = {
  Fixed: 'partials.fixed',
  Repairable: 'partials.repairable',
  'End of life': 'partials.end',
}

const STATUS_VARIANTS = {
  Fixed: 'success',
  Repairable: 'warning',
  'End of life': 'danger',
}

export function deviceStatusKey(device) {
  return STATUS_KEYS[device?.repair_status] || null
}

export function deviceStatusVariant(device) {
  return STATUS_VARIANTS[device?.repair_status] || 'secondary'
}

// The spare-parts tick only shows when parts actually need sourcing
// (SPARE_PARTS_MANUFACTURER / SPARE_PARTS_THIRD_PARTY per constants.js) -
// NOT for SPARE_PARTS_NOT_NEEDED ('No') or an unset value. Ported from
// EventDeviceSummary.vue's `sparePartsNeeded` computed.
export function deviceSparePartsNeeded(device) {
  return device?.spare_parts === 'Manufacturer' || device?.spare_parts === 'Third party'
}
