import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DeviceForm from '../../../app/components/devices/DeviceForm.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BFormStub = { emits: ['submit'], template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const DevicePhotosStub = {
  props: ['eventId', 'deviceId', 'images', 'readonly'],
  template: '<div data-testid="device-photos-stub" :data-readonly="readonly" />',
}
// Same shape as DevicesSearchTable.spec.js's BModalStub.
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}

const GLOBAL_STUBS = {
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BModal: BModalStub,
  DevicePhotos: DevicePhotosStub,
}

function seedMeta(store) {
  store.itemTypes.data = [
    { type: 'Blender', powered: true, idcategories: 30, categoryname: 'Kitchen' },
    { type: 'Sofa', powered: false, idcategories: 31, categoryname: 'Furniture' },
  ]
  store.itemTypes.loaded = true

  store.categories.data = [
    { id: 10, name: 'Toaster', powered: true, cluster: 1, cluster_name: 'Kitchen' },
    { id: 20, name: 'Bicycle', powered: false, cluster: 2, cluster_name: 'Outdoors' },
  ]
  store.categories.loaded = true

  store.clusterHeaders.data = [
    { id: 1, name: 'Kitchen' },
    { id: 2, name: 'Outdoors' },
  ]
  store.clusterHeaders.loaded = true

  store.brands.data = [{ id: 1, brand_name: 'Acme' }]
  store.brands.loaded = true

  store.options.data = {
    barriers: [{ id: 1, name: 'Spare parts not available' }],
    spare_parts: ['No', 'Manufacturer', 'Third party'],
    next_steps: ['More time needed', 'Professional help', 'Do it yourself'],
  }
  store.options.loaded = true
}

function mountForm(props = {}) {
  // devices.title_repair/unknown_item_type/unknown_brand (gaps 7 and 13)
  // aren't in the checked-in i18n/locales fixtures yet (they're
  // regenerated from lang/en/devices.php by `translations:export-client`,
  // which this task deliberately doesn't run) - overlay them here instead.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        devices: {
          ...en.devices,
          title_repair: 'REPAIR',
          unknown_item_type: "You're creating a new item type. Are you sure an existing item type is not suitable?",
          unknown_brand: "You're creating a new brand. Are you sure an existing brand is not suitable?",
        },
      },
    },
  })

  return mount(DeviceForm, {
    props: { eventId: 5, powered: true, ...props },
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('components/devices/DeviceForm', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({ $api: { device: {} } }))
    store = useDevicesStore()
    seedMeta(store)
  })

  // DeviceCategorySelect/DeviceType/DeviceModel/DeviceProblem/DeviceNotes
  // each carry an info popover; ours had none. Asserting the count rather
  // than mere presence, because four of the five rendering and one silently
  // missing would still look fine on screen.
  it('shows an info popover on each of the five documented fields', () => {
    const wrapper = mountForm()

    expect(wrapper.findAll('[data-testid="field-info-toggle"]')).toHaveLength(5)
  })

  // The item-type help text differs by powered-ness: the examples are
  // appliances vs furniture/clothing.
  it('picks the item-type tooltip to match powered-ness', () => {
    const powered = mountForm({ powered: true })
    const unpowered = mountForm({ powered: false })

    expect(powered.findComponent({ name: 'FieldInfoPopover' }).props('content'))
      .toBe(en.devices.tooltip_type_powered)
    expect(unpowered.findComponent({ name: 'FieldInfoPopover' }).props('content'))
      .toBe(en.devices.tooltip_type_unpowered)
  })

  it('requires a category before submitting', async () => {
    store.addDevice = vi.fn()
    const wrapper = mountForm()

    await wrapper.find('[data-testid="device-form"]').trigger('submit')

    expect(wrapper.find('[data-testid="device-form-category-error"]').exists()).toBe(true)
    expect(store.addDevice).not.toHaveBeenCalled()
  })

  it('submits the exact payload field names validateDeviceParams expects (create)', async () => {
    store.addDevice = vi.fn().mockResolvedValue({ device: { id: 42 } })
    const wrapper = mountForm()

    await wrapper.find('[data-testid="device-form-item-type"]').setValue('Toaster')
    await wrapper.find('[data-testid="device-form-category"]').setValue('10')
    await wrapper.find('[data-testid="device-form-brand"]').setValue('Acme')
    await wrapper.find('[data-testid="device-form-model"]').setValue('9000')
    await wrapper.find('[data-testid="device-form-age"]').setValue('2')
    await wrapper.find('[data-testid="device-form-problem"]').setValue('Would not toast')
    await wrapper.find('[data-testid="device-form-notes"]').setValue('Needed a new element')
    await wrapper.find('[data-testid="device-form-status"]').setValue('Fixed')
    await wrapper.find('[data-testid="device-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(store.addDevice).toHaveBeenCalledWith(5, {
      category: 10,
      item_type: 'Toaster',
      brand: 'Acme',
      model: '9000',
      age: 2,
      estimate: 0,
      problem: 'Would not toast',
      notes: 'Needed a new element',
      repair_status: 'Fixed',
      // Empty optionals are OMITTED, not sent as null: the endpoint's
      // pre-existing validation ('brand' => 'string' etc., no nullable)
      // 422s on null values - the shape the legacy client always sent.
    })
  })

  it('loops addDevice once per unit of quantity', async () => {
    store.addDevice = vi.fn().mockResolvedValue({ device: { id: 1 } })
    const wrapper = mountForm()

    await wrapper.find('[data-testid="device-form-category"]').setValue('10')
    await wrapper.find('[data-testid="device-form-quantity"]').setValue('3')
    await wrapper.find('[data-testid="device-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(store.addDevice).toHaveBeenCalledTimes(3)
  })

  it('emits saved after a successful create', async () => {
    store.addDevice = vi.fn().mockResolvedValue({ device: { id: 42 } })
    const wrapper = mountForm()

    await wrapper.find('[data-testid="device-form-category"]').setValue('10')
    await wrapper.find('[data-testid="device-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('saved')).toBeTruthy()
  })

  it('shows a generic error and does not emit saved when the store call fails', async () => {
    store.addDevice = vi.fn().mockRejectedValue({ status: 500 })
    const wrapper = mountForm()

    await wrapper.find('[data-testid="device-form-category"]').setValue('10')
    await wrapper.find('[data-testid="device-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="device-form-error"]').exists()).toBe(true)
    expect(wrapper.emitted('saved')).toBeFalsy()
  })

  it('emits cancel when Cancel is clicked', async () => {
    const wrapper = mountForm()
    await wrapper.find('[data-testid="device-form-cancel"]').trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  describe('conditional repair-status fields', () => {
    it('shows next steps + spare parts for Repairable, hides barrier', async () => {
      const wrapper = mountForm()
      await wrapper.find('[data-testid="device-form-status"]').setValue('Repairable')

      expect(wrapper.find('[data-testid="device-form-next-steps"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-form-spare-parts"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-form-barrier"]').exists()).toBe(false)
    })

    it('shows only spare parts for Fixed', async () => {
      const wrapper = mountForm()
      await wrapper.find('[data-testid="device-form-status"]').setValue('Fixed')

      expect(wrapper.find('[data-testid="device-form-next-steps"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-spare-parts"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-form-barrier"]').exists()).toBe(false)
    })

    it('shows only barrier for End of life', async () => {
      const wrapper = mountForm()
      await wrapper.find('[data-testid="device-form-status"]').setValue('End of life')

      expect(wrapper.find('[data-testid="device-form-next-steps"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-spare-parts"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-barrier"]').exists()).toBe(true)
    })
  })

  describe('weight field visibility (EventDevice.vue: showWeight)', () => {
    it('is always shown for unpowered devices', async () => {
      const wrapper = mountForm({ powered: false })
      await wrapper.find('[data-testid="device-form-category"]').setValue('20')
      expect(wrapper.find('[data-testid="device-form-weight"]').exists()).toBe(true)
    })

    it('is hidden for powered devices unless the misc category (46) is chosen', async () => {
      const wrapper = mountForm({ powered: true })
      await wrapper.find('[data-testid="device-form-category"]').setValue('10')
      expect(wrapper.find('[data-testid="device-form-weight"]').exists()).toBe(false)

      await wrapper.find('[data-testid="device-form-category"]').setValue('46')
      expect(wrapper.find('[data-testid="device-form-weight"]').exists()).toBe(true)
    })
  })

  describe('category suggestion (EXACT-match port)', () => {
    it('applies a matching cluster category suggestion as the user types the item type (add mode)', async () => {
      const wrapper = mountForm({ powered: true })
      await wrapper.find('[data-testid="device-form-item-type"]').setValue('Toaster')

      expect(wrapper.find('[data-testid="device-form-category"]').element.value).toBe('10')
    })

    it('does not overwrite an existing category when editing', async () => {
      const wrapper = mountForm({
        powered: true,
        device: { id: 7, item_type: '', category: { id: 20, name: 'Bicycle', powered: false }, images: [] },
      })

      await wrapper.find('[data-testid="device-form-item-type"]').setValue('Toaster')

      // The category select stays on the device's original category (20),
      // not the "Toaster" suggestion (10), because editing.value is true
      // and form.category was already non-empty.
      expect(wrapper.find('[data-testid="device-form-category"]').element.value).toBe('20')
    })
  })

  describe('edit mode', () => {
    function editDevice(overrides = {}) {
      return {
        id: 7,
        item_type: 'Kettle',
        category: { id: 10, name: 'Toaster', powered: true },
        brand: 'Acme',
        model: 'K1',
        age: 2,
        estimate: 0,
        problem: 'Broken',
        notes: 'Some notes',
        repair_status: 'Fixed',
        next_steps: null,
        spare_parts: 'No',
        barrier: null,
        images: [{ idxref: 1, path: 'a.jpg' }],
        ...overrides,
      }
    }

    it('prefills the form from the device prop', () => {
      const wrapper = mountForm({ device: editDevice() })

      expect(wrapper.find('[data-testid="device-form-item-type"]').element.value).toBe('Kettle')
      expect(wrapper.find('[data-testid="device-form-category"]').element.value).toBe('10')
      expect(wrapper.find('[data-testid="device-form-brand"]').element.value).toBe('Acme')
      expect(wrapper.find('[data-testid="device-form-model"]').element.value).toBe('K1')
    })

    it('shows DevicePhotos with the device id and existing images', () => {
      const wrapper = mountForm({ device: editDevice() })
      const photos = wrapper.findComponent(DevicePhotosStub)
      expect(photos.exists()).toBe(true)
      expect(photos.props('deviceId')).toBe(7)
      expect(photos.props('images')).toEqual([{ idxref: 1, path: 'a.jpg' }])
    })

    it('does not show quantity when editing', () => {
      const wrapper = mountForm({ device: editDevice() })
      expect(wrapper.find('[data-testid="device-form-quantity"]').exists()).toBe(false)
    })

    it('submits an update keyed by the device id, not addDevice', async () => {
      store.updateDevice = vi.fn().mockResolvedValue({ device: editDevice() })
      const wrapper = mountForm({ device: editDevice() })

      await wrapper.find('[data-testid="device-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.updateDevice).toHaveBeenCalledWith(5, 7, expect.objectContaining({ category: 10, item_type: 'Kettle' }))
    })
  })

  describe('three-card layout (EventDevice.vue port, gap 7)', () => {
    it('renders the Item/Repair/Assessment cards, each with its own header', () => {
      const wrapper = mountForm()

      const item = wrapper.find('[data-testid="device-form-card-item"]')
      const repair = wrapper.find('[data-testid="device-form-card-repair"]')
      const assessment = wrapper.find('[data-testid="device-form-card-assessment"]')

      expect(item.exists()).toBe(true)
      expect(item.find('h3').text()).toBe('ITEM')
      expect(item.find('[data-testid="device-form-item-type"]').exists()).toBe(true)
      expect(item.find('[data-testid="device-form-brand"]').exists()).toBe(true)

      expect(repair.exists()).toBe(true)
      expect(repair.find('h3').text()).toBe('REPAIR')
      expect(repair.find('[data-testid="device-form-status"]').exists()).toBe(true)

      expect(assessment.exists()).toBe(true)
      expect(assessment.find('h3').text()).toBe('ASSESSMENT')
      expect(assessment.find('[data-testid="device-form-problem"]').exists()).toBe(true)
      expect(assessment.find('[data-testid="device-form-notes"]').exists()).toBe(true)
    })
  })

  describe('unknown item-type/brand warning (gap 33)', () => {
    it('warns when the typed item type matches no suggestion', async () => {
      const wrapper = mountForm()
      expect(wrapper.find('[data-testid="device-form-item-type-warning"]').exists()).toBe(false)

      await wrapper.find('[data-testid="device-form-item-type"]').setValue('Not A Real Type')
      expect(wrapper.find('[data-testid="device-form-item-type-warning"]').exists()).toBe(true)

      await wrapper.find('[data-testid="device-form-item-type"]').setValue('Blender')
      expect(wrapper.find('[data-testid="device-form-item-type-warning"]').exists()).toBe(false)
    })

    it('warns when the typed brand matches no suggestion', async () => {
      const wrapper = mountForm()
      expect(wrapper.find('[data-testid="device-form-brand-warning"]').exists()).toBe(false)

      await wrapper.find('[data-testid="device-form-brand"]').setValue('Not A Real Brand')
      expect(wrapper.find('[data-testid="device-form-brand-warning"]').exists()).toBe(true)

      await wrapper.find('[data-testid="device-form-brand"]').setValue('Acme')
      expect(wrapper.find('[data-testid="device-form-brand-warning"]').exists()).toBe(false)
    })

    it('does not warn when editing and the field still matches the device`s saved value', () => {
      const wrapper = mountForm({
        device: { id: 7, item_type: 'A Legacy Free-Text Type', brand: 'A Legacy Free-Text Brand', category: { id: 10 }, images: [] },
      })

      expect(wrapper.find('[data-testid="device-form-item-type-warning"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-brand-warning"]').exists()).toBe(false)
    })
  })

  // Gap fix (HIGH): components/fixometer/DevicesSearchTable.vue's row-
  // details panel reuses this exact form - readonly for non-admins, or
  // editable with an inline delete for admins - via these three props
  // (EventDevice.vue's own `disabled`/`deleteButton`/`cancelButton`).
  describe('readonly/deleteButton/cancelButton (gap 2/3)', () => {
    function editDevice(overrides = {}) {
      return {
        id: 7,
        item_type: 'Kettle',
        category: { id: 10, name: 'Toaster', powered: true },
        brand: 'Acme',
        model: 'K1',
        age: 2,
        estimate: 0,
        problem: 'Broken',
        notes: 'Some notes',
        repair_status: 'Fixed',
        images: [],
        ...overrides,
      }
    }

    it('disables every field and hides the button row entirely when readonly', () => {
      const wrapper = mountForm({ device: editDevice(), readonly: true })

      for (const testid of ['device-form-item-type', 'device-form-category', 'device-form-brand', 'device-form-model', 'device-form-age', 'device-form-status']) {
        expect(wrapper.find(`[data-testid="${testid}"]`).attributes('disabled')).toBeDefined()
      }
      expect(wrapper.find('[data-testid="device-photos-stub"]').attributes('data-readonly')).toBe('true')
      expect(wrapper.find('[data-testid="device-form-submit"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-cancel"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-form-delete"]').exists()).toBe(false)
    })

    it('hides the Cancel button when cancelButton is false, but keeps Save', () => {
      const wrapper = mountForm({ device: editDevice(), cancelButton: false })

      expect(wrapper.find('[data-testid="device-form-submit"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-form-cancel"]').exists()).toBe(false)
    })

    it('shows an inline Delete button behind its own confirm modal when deleteButton is true', async () => {
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockResolvedValue()

      const wrapper = mountForm({ device: editDevice(), deleteButton: true, cancelButton: false })

      expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
      await wrapper.find('[data-testid="device-form-delete"]').trigger('click')
      expect(store.deleteDevice).not.toHaveBeenCalled()
      expect(wrapper.find('[data-modal-title]').exists()).toBe(true)

      await wrapper.find('[data-testid="device-form-delete-confirm"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(store.deleteDevice).toHaveBeenCalledWith(5, 7)
      expect(wrapper.emitted('deleted')).toBeTruthy()
    })

    it('shows a generic error and does not emit deleted when the delete call fails', async () => {
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockRejectedValue({ status: 500 })

      const wrapper = mountForm({ device: editDevice(), deleteButton: true })

      await wrapper.find('[data-testid="device-form-delete"]').trigger('click')
      await wrapper.find('[data-testid="device-form-delete-confirm"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="device-form-error"]').exists()).toBe(true)
      expect(wrapper.emitted('deleted')).toBeFalsy()
    })

    it('does not call the store when the delete confirm is cancelled', async () => {
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockResolvedValue()

      const wrapper = mountForm({ device: editDevice(), deleteButton: true })

      await wrapper.find('[data-testid="device-form-delete"]').trigger('click')
      await wrapper.find('[data-testid="device-form-delete-cancel"]').trigger('click')

      expect(store.deleteDevice).not.toHaveBeenCalled()
      expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
    })
  })
})
