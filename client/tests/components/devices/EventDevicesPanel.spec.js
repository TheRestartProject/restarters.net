import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventDevicesPanel from '../../../app/components/devices/EventDevicesPanel.vue'
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

function mountPanel(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventDevicesPanel, {
    props: { eventId: 5, devices: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { BButton: BButtonStub, DeviceRow: DeviceRowStub, DeviceForm: DeviceFormStub },
    },
  })
}

describe('components/devices/EventDevicesPanel', () => {
  it('shows a loading skeleton and no tabs while loading', () => {
    const wrapper = mountPanel({ loading: true })
    expect(wrapper.find('[data-testid="event-devices-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-devices-tab-powered"]').exists()).toBe(false)
  })

  it('splits devices into powered/unpowered tabs by category.powered', async () => {
    const powered = device({ id: 1, category: { powered: true } })
    const unpowered = device({ id: 2, category: { powered: false } })
    const wrapper = mountPanel({ devices: [powered, unpowered] })

    expect(wrapper.find('[data-testid="row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="row-2"]').exists()).toBe(false)

    await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')

    expect(wrapper.find('[data-testid="row-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="row-1"]').exists()).toBe(false)
  })

  it('does not show add buttons or the canedit column when canedit is false', () => {
    const wrapper = mountPanel({ canedit: false })
    expect(wrapper.find('[data-testid="add-powered-device-desktop"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="add-unpowered-device-desktop"]').exists()).toBe(false)
  })

  it('shows the legacy .add-powered-device-desktop/.add-unpowered-device-desktop equivalents when canedit', async () => {
    const wrapper = mountPanel({ canedit: true })
    expect(wrapper.find('[data-testid="add-powered-device-desktop"]').exists()).toBe(true)

    await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')
    expect(wrapper.find('[data-testid="add-unpowered-device-desktop"]').exists()).toBe(true)
  })

  it('toggles the add-powered DeviceForm open and closed, and hides it again when saved', async () => {
    const wrapper = mountPanel({ canedit: true })

    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(false)

    await wrapper.find('[data-testid="add-powered-device-desktop"]').trigger('click')
    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(true)

    await wrapper.findComponent(DeviceFormStub).vm.$emit('saved', { id: 9 })
    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(false)
  })

  it('passes eventId/canedit through to DeviceRow', () => {
    const wrapper = mountPanel({ eventId: 42, canedit: true, devices: [device({ id: 1 })] })
    const row = wrapper.findComponent(DeviceRowStub)
    expect(row.props('eventId')).toBe(42)
    expect(row.props('canedit')).toBe(true)
  })

  it('shows the empty state when a tab has no devices', () => {
    const wrapper = mountPanel({ devices: [] })
    expect(wrapper.find('[data-testid="event-devices-empty"]').exists()).toBe(true)
  })

  // Gap D5: same devices.description_powered/description_unpowered copy
  // DevicesSearchTable.vue shows under its powered/unpowered toggle.
  describe('powered/unpowered description (gap D5)', () => {
    it('shows the powered-item description on the powered tab', () => {
      const wrapper = mountPanel()
      expect(wrapper.find('[data-testid="event-devices-description"]').text()).toContain('powered item')
    })

    it('switches to the unpowered-item description on the unpowered tab', async () => {
      const wrapper = mountPanel()
      await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')
      expect(wrapper.find('[data-testid="event-devices-description"]').text()).toContain('unpowered item')
    })
  })
})
