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

  // Gap 23: the desktop tabs (event-devices-desktop) show one category's
  // table at a time; the mobile two-collapsible layout (a separate DOM
  // subtree, see below) shows both simultaneously - so this scopes its
  // assertions to the desktop subtree specifically.
  it('splits devices into powered/unpowered tabs by category.powered (desktop)', async () => {
    const powered = device({ id: 1, category: { powered: true } })
    const unpowered = device({ id: 2, category: { powered: false } })
    const wrapper = mountPanel({ devices: [powered, unpowered] })
    const desktop = wrapper.find('[data-testid="event-devices-desktop"]')

    expect(desktop.find('[data-testid="row-1"]').exists()).toBe(true)
    expect(desktop.find('[data-testid="row-2"]').exists()).toBe(false)

    await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')

    expect(desktop.find('[data-testid="row-2"]').exists()).toBe(true)
    expect(desktop.find('[data-testid="row-1"]').exists()).toBe(false)
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

  // Gap fix (HIGH): legacy wraps the whole panel in <CollapsibleSection
  // collapsed> - collapsed by default on mobile - not a bare, always-
  // expanded heading.
  it('is collapsed on mobile by default (CollapsibleSection collapsed-on-mobile)', () => {
    const wrapper = mountPanel()
    const toggle = wrapper.find('[data-testid="collapsible-toggle"]')
    expect(toggle.exists()).toBe(true)
    expect(toggle.attributes('aria-expanded')).toBe('false')
  })

  it('carries the #devices-section anchor id AddDataModal links to', () => {
    const wrapper = mountPanel()
    expect(wrapper.attributes('id')).toBe('devices-section')
  })

  // Gap fix (MEDIUM): legacy pairs each waste/CO2 figure in the tab title
  // with its own icon (trash_brand.svg / co2_brand.svg), not plain text.
  describe('tab waste/CO2 icons (gap 8)', () => {
    it('shows the waste and CO2 icons beside their figures when stats are supplied', () => {
      const wrapper = mountPanel({ stats: { waste_powered: 12.4, co2_powered: 3.2, waste_unpowered: 0, co2_unpowered: 0 } })
      const tab = wrapper.find('[data-testid="event-devices-tab-powered"]')

      const icons = tab.findAll('img')
      expect(icons.map((i) => i.attributes('src'))).toEqual(['/images/trash_brand.svg', '/images/co2_brand.svg'])
      expect(tab.text()).toContain('12 kg')
      expect(tab.text()).toContain('3 kg')
    })

    it('shows no impact figures when stats is null', () => {
      const wrapper = mountPanel({ stats: null })
      expect(wrapper.find('[data-testid="event-devices-tab-powered"]').findAll('img')).toHaveLength(0)
    })
  })

  // Gap 23: EventDevices.vue pairs the "Items at this event" heading with a
  // TV icon (tv.svg), desktop-only.
  it('shows the TV icon beside the heading, desktop-only', () => {
    const wrapper = mountPanel()
    const icon = wrapper.find('.devices-title-icon')
    expect(icon.exists()).toBe(true)
    expect(icon.attributes('src')).toBe('/images/tv.svg')
    expect(icon.classes()).toContain('d-none')
    expect(icon.classes()).toContain('d-md-block')
  })

  // Gap 23: mobile replaces the desktop tabs with two independent,
  // individually-collapsible sections (both potentially open/visible at
  // once) - EventDevices.vue's `d-block d-md-none` branch.
  describe('mobile double-collapsible layout (gap 23)', () => {
    it('shows both a powered and an unpowered mobile section, each collapsed by default', () => {
      const wrapper = mountPanel()
      const mobile = wrapper.find('[data-testid="event-devices-mobile"]')

      const poweredSection = mobile.find('[data-testid="event-devices-powered-mobile"]')
      const unpoweredSection = mobile.find('[data-testid="event-devices-unpowered-mobile"]')
      expect(poweredSection.exists()).toBe(true)
      expect(unpoweredSection.exists()).toBe(true)

      expect(poweredSection.find('[data-testid="collapsible-toggle"]').attributes('aria-expanded')).toBe('false')
      expect(unpoweredSection.find('[data-testid="collapsible-toggle"]').attributes('aria-expanded')).toBe('false')
    })

    it('shows each category\'s own devices in its own mobile section, independent of the desktop active tab', () => {
      const powered = device({ id: 1, category: { powered: true } })
      const unpowered = device({ id: 2, category: { powered: false } })
      const wrapper = mountPanel({ devices: [powered, unpowered] })
      const mobile = wrapper.find('[data-testid="event-devices-mobile"]')

      expect(mobile.find('[data-testid="event-devices-powered-mobile"]').find('[data-testid="row-1"]').exists()).toBe(true)
      expect(mobile.find('[data-testid="event-devices-unpowered-mobile"]').find('[data-testid="row-2"]').exists()).toBe(true)
    })

    it('shows the mobile add-device buttons under their own testids when canedit', () => {
      const wrapper = mountPanel({ canedit: true })
      expect(wrapper.find('[data-testid="add-powered-device-mobile"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="add-unpowered-device-mobile"]').exists()).toBe(true)
    })

    it('opens the DeviceForm from the mobile add button, sharing state with the desktop add button', async () => {
      const wrapper = mountPanel({ canedit: true })

      await wrapper.find('[data-testid="add-powered-device-mobile"]').trigger('click')
      expect(wrapper.findAllComponents(DeviceFormStub)).toHaveLength(2)

      await wrapper.findComponent(DeviceFormStub).vm.$emit('saved', { id: 9 })
      expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(false)
    })
  })
})
