import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DeviceRow from '../../../app/components/devices/DeviceRow.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const DeviceFormStub = {
  props: ['eventId', 'device', 'powered'],
  emits: ['saved', 'cancel'],
  template: '<div data-testid="device-form-stub">{{ device && device.id }}</div>',
}

function device(overrides = {}) {
  return {
    id: 1,
    item_type: 'Toaster',
    category: { id: 10, name: 'devices.category_toasters', powered: true },
    brand: 'Acme',
    age: '3',
    short_problem: "Won't heat",
    repair_status: 'Fixed',
    spare_parts: 'No',
    ...overrides,
  }
}

function mountRow(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, devices: { ...en.devices, category_toasters: 'Toasters' } } },
  })

  return mount(DeviceRow, {
    props: { device: device(), eventId: 5, powered: true, ...props },
    global: {
      plugins: [i18n],
      stubs: { BBadge: BBadgeStub, DeviceForm: DeviceFormStub },
    },
  })
}

describe('components/devices/DeviceRow', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({ $api: { device: {}, event: {} } }))
  })

  it('renders the device summary fields', () => {
    const wrapper = mountRow()

    expect(wrapper.find('[data-testid="event-device-1"]').text()).toContain('Toaster')
    expect(wrapper.find('[data-testid="event-device-1"]').text()).toContain('Toasters')
    expect(wrapper.find('[data-testid="event-device-1"]').text()).toContain('Acme')
    expect(wrapper.find('[data-testid="event-device-status-1"]').text()).toBe('Fixed')
  })

  it('hides the brand column when unpowered', () => {
    const wrapper = mountRow({ powered: false })
    expect(wrapper.find('[data-testid="event-device-1"]').text()).not.toContain('Acme')
  })

  describe('spare-parts tick semantics (legacy EventDeviceSummary.vue: tick only for parts that need sourcing)', () => {
    it('shows the tick for Manufacturer', () => {
      const wrapper = mountRow({ device: device({ spare_parts: 'Manufacturer' }) })
      expect(wrapper.find('[data-testid="event-device-spare-parts-1"]').exists()).toBe(true)
    })

    it('shows the tick for Third party', () => {
      const wrapper = mountRow({ device: device({ spare_parts: 'Third party' }) })
      expect(wrapper.find('[data-testid="event-device-spare-parts-1"]').exists()).toBe(true)
    })

    it('does not show the tick for "No" (not needed)', () => {
      const wrapper = mountRow({ device: device({ spare_parts: 'No' }) })
      expect(wrapper.find('[data-testid="event-device-spare-parts-1"]').exists()).toBe(false)
    })

    it('does not show the tick when unset', () => {
      const wrapper = mountRow({ device: device({ spare_parts: null }) })
      expect(wrapper.find('[data-testid="event-device-spare-parts-1"]').exists()).toBe(false)
    })
  })

  it('does not show the edit/delete column when canedit is false', () => {
    const wrapper = mountRow({ canedit: false })
    expect(wrapper.find('[data-testid="event-device-edit-1"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="event-device-delete-1"]').exists()).toBe(false)
  })

  it('swaps the row for DeviceForm when Edit is clicked, and back on saved/cancel', async () => {
    const wrapper = mountRow({ canedit: true })

    await wrapper.find('[data-testid="event-device-edit-1"]').trigger('click')
    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-device-1"]').exists()).toBe(false)

    await wrapper.findComponent(DeviceFormStub).vm.$emit('cancel')
    expect(wrapper.find('[data-testid="device-form-stub"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="event-device-1"]').exists()).toBe(true)
  })

  it('requires a delete confirm click before calling the store', async () => {
    const store = useDevicesStore()
    store.deleteDevice = vi.fn().mockResolvedValue()

    const wrapper = mountRow({ canedit: true })

    await wrapper.find('[data-testid="event-device-delete-1"]').trigger('click')
    expect(store.deleteDevice).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="event-device-delete-confirm-1"]').exists()).toBe(true)

    await wrapper.find('[data-testid="event-device-delete-confirm-1"]').trigger('click')
    expect(store.deleteDevice).toHaveBeenCalledWith(5, 1)
  })

  it('shows an error message when delete fails', async () => {
    const store = useDevicesStore()
    store.deleteDevice = vi.fn().mockRejectedValue({ status: 500 })

    const wrapper = mountRow({ canedit: true })
    await wrapper.find('[data-testid="event-device-delete-1"]').trigger('click')
    await wrapper.find('[data-testid="event-device-delete-confirm-1"]').trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain("Sorry, we couldn't delete that item")
  })
})
