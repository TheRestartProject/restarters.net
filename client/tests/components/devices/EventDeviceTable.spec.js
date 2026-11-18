import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventDeviceTable from '../../../app/components/devices/EventDeviceTable.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const DeviceRowStub = {
  props: ['device', 'eventId', 'powered', 'canedit'],
  template: '<tr :data-testid="`row-${device.id}`" />',
}
const DeviceFormStub = {
  props: ['eventId', 'powered'],
  emits: ['saved', 'cancel'],
  template: '<div data-testid="device-form-stub" />',
}

function device(overrides = {}) {
  return { id: 1, category: { powered: true }, ...overrides }
}

function mountTable(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventDeviceTable, {
    props: { devices: [], powered: true, eventId: 5, ...props },
    global: {
      plugins: [i18n],
      stubs: { BButton: BButtonStub, DeviceRow: DeviceRowStub, DeviceForm: DeviceFormStub },
    },
  })
}

// Factored out of EventDevicesPanel.vue (gap 23) so the same table markup
// can be rendered both in the desktop tabs and per mobile collapsible
// (EventDevices.vue's own layout) without quadrupling it across the two
// categories x two layouts - the `variant` prop only changes testids, not
// behaviour, so the desktop-variant defaults keep the pre-refactor testids
// unchanged.
describe('components/devices/EventDeviceTable', () => {
  it('renders a device row per device, and defaults to desktop testids', () => {
    const wrapper = mountTable({ devices: [device({ id: 1 }), device({ id: 2 })] })

    expect(wrapper.find('[data-testid="event-devices-table-powered"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="row-2"]').exists()).toBe(true)
  })

  it('shows the Brand column only when powered', () => {
    const powered = mountTable({ powered: true })
    const unpowered = mountTable({ powered: false })

    expect(powered.find('[data-testid="event-devices-table-powered"]').text()).toContain('Brand')
    expect(unpowered.find('[data-testid="event-devices-table-unpowered"]').text()).not.toContain('Brand')
  })

  it('shows the empty-state row with the right colspan (7 powered / 6 unpowered, +1 each when canedit)', () => {
    expect(mountTable({ powered: true, canedit: false }).find('[data-testid="event-devices-empty"]').attributes('colspan')).toBe('7')
    expect(mountTable({ powered: true, canedit: true }).find('[data-testid="event-devices-empty"]').attributes('colspan')).toBe('8')
    expect(mountTable({ powered: false, canedit: false }).find('[data-testid="event-devices-empty"]').attributes('colspan')).toBe('6')
    expect(mountTable({ powered: false, canedit: true }).find('[data-testid="event-devices-empty"]').attributes('colspan')).toBe('7')
  })

  it('uses -mobile-suffixed testids for the mobile variant, distinct from the desktop ones', () => {
    const wrapper = mountTable({ powered: true, variant: 'mobile' })
    expect(wrapper.find('[data-testid="event-devices-table-powered-mobile"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-devices-table-powered"]').exists()).toBe(false)
  })

  it('emits toggle-add when the add button is clicked, and shows DeviceForm only when adding is true', async () => {
    const wrapper = mountTable({ canedit: true, adding: false })

    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(false)
    await wrapper.find('[data-testid="add-powered-device-desktop"]').trigger('click')
    expect(wrapper.emitted('toggle-add')).toBeTruthy()

    await wrapper.setProps({ adding: true })
    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(true)
    expect(wrapper.findComponent(DeviceFormStub).props('powered')).toBe(true)
  })

  it('emits saved when DeviceForm reports a save', async () => {
    const wrapper = mountTable({ canedit: true, adding: true })
    await wrapper.findComponent(DeviceFormStub).vm.$emit('saved', { id: 9 })
    expect(wrapper.emitted('saved')).toBeTruthy()
  })

  it('does not show the add button or the canedit column when canedit is false', () => {
    const wrapper = mountTable({ canedit: false })
    expect(wrapper.find('[data-testid="add-powered-device-desktop"]').exists()).toBe(false)
  })

  // Gap fix (dropped-behaviour finding): EventDevices.vue's add-device
  // buttons lead with `b-img :src="imageUrl('/images/add-icon.svg')"`.
  it('shows the add-icon image before the label, for both powered and unpowered', () => {
    const powered = mountTable({ canedit: true, powered: true })
    const unpowered = mountTable({ canedit: true, powered: false })

    expect(powered.find('[data-testid="add-powered-device-desktop"] img').attributes('src')).toBe('/images/add-icon.svg')
    expect(unpowered.find('[data-testid="add-unpowered-device-desktop"] img').attributes('src')).toBe('/images/add-icon.svg')
  })
})
