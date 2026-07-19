import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupForm from '../../../app/components/groups/GroupForm.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// GroupForm.vue's location-preview map (gap 10) uses real @vue-leaflet/
// vue-leaflet, which does its own dynamic import()s of Leaflet's marker
// PNGs to patch L.Icon.Default - that needs a bundler/browser, not Node's
// module loader, so mount it as plain stubs instead (same approach
// GroupMap.spec.js already takes for the full group-finder map).
const { LMapStub, LTileLayerStub, LMarkerStub } = vi.hoisted(() => ({
  LMapStub: {
    name: 'LMap',
    props: ['zoom', 'center', 'useGlobalLeaflet'],
    template: '<div class="stub-lmap"><slot /></div>',
  },
  LTileLayerStub: {
    name: 'LTileLayer',
    props: ['url', 'attribution'],
    template: '<div class="stub-ltilelayer" />',
  },
  LMarkerStub: {
    name: 'LMarker',
    props: ['latLng', 'icon'],
    template: '<div class="stub-lmarker" />',
  },
}))

vi.mock('@vue-leaflet/vue-leaflet', () => ({ LMap: LMapStub, LTileLayer: LTileLayerStub, LMarker: LMarkerStub }))

// RichTextEditor/LocationPicker are unit-tested on their own
// (tests/components/forms/*.spec.js) - stub them here to plain inputs so
// GroupForm's own logic (payload shape, permission gating, validation, 422
// rendering) is what's under test.
const RichTextEditorStub = {
  props: ['modelValue', 'hasError', 'testid'],
  emits: ['update:modelValue'],
  template: '<textarea :data-testid="testid" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
}
const LocationPickerStub = {
  props: ['location', 'postcode', 'lat', 'lng', 'hasError', 'canEditPostcode'],
  emits: ['update:location', 'update:postcode', 'update:lat', 'update:lng'],
  template:
    '<input data-testid="location-picker-input" :value="location" @input="$emit(\'update:location\', $event.target.value)" />',
}
const BFormStub = {
  emits: ['submit'],
  template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>',
}
const BFormGroupStub = { template: '<div><slot /></div>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BCardStub = { template: '<div><slot /></div>' }
const BCardHeaderStub = { template: '<div><slot /></div>' }
const BCardBodyStub = { template: '<div><slot /></div>' }
const TusImageUploadStub = {
  props: ['currentImageUrl'],
  emits: ['uploaded', 'upload-error'],
  template:
    '<div data-testid="stub-tus-image-upload"><button data-testid="stub-upload-ok" @click="$emit(\'uploaded\', { uploadKey: \'key123\' })" /><button data-testid="stub-upload-fail" @click="$emit(\'upload-error\', \'boom\')" /></div>',
}
const BFormCheckboxGroupStub = {
  props: ['modelValue', 'options'],
  emits: ['update:modelValue'],
  template: '<div><label v-for="o in options" :key="o.value"><input type="checkbox" :value="o.value" @change="toggle(o.value)"> {{ o.text }}</label></div>',
  methods: {
    toggle(value) {
      const current = this.modelValue || []
      const next = current.includes(value) ? current.filter((v) => v !== value) : [...current, value]
      this.$emit('update:modelValue', next)
    },
  },
}

const GLOBAL_STUBS = {
  RichTextEditor: RichTextEditorStub,
  LocationPicker: LocationPickerStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BCard: BCardStub,
  BCardHeader: BCardHeaderStub,
  BCardBody: BCardBodyStub,
  BFormCheckboxGroup: BFormCheckboxGroupStub,
  TusImageUpload: TusImageUploadStub,
}

function mountForm(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupForm, {
    props,
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

async function fillRequiredFields(wrapper) {
  await wrapper.find('[data-testid="group-form-name"]').setValue('Fixers United')
  await wrapper.find('[data-testid="group-form-description"]').setValue('<p>We fix stuff</p>')
  await wrapper.find('[data-testid="location-picker-input"]').setValue('Anytown')
}

describe('components/groups/GroupForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({
      $api: {
        config: { timezones: vi.fn().mockResolvedValue([{ name: 'Europe/London' }]) },
        network: { list: vi.fn().mockResolvedValue({ data: [{ id: 1, name: 'UK Network' }] }) },
      },
    }))
  })

  describe('create mode', () => {
    it('shows validation errors and does not submit when required fields are empty', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn()

      const wrapper = mountForm()
      await wrapper.find('[data-testid="group-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="group-form-name-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-form-description-error"]').exists()).toBe(true)
      expect(store.createGroup).not.toHaveBeenCalled()
    })

    it('submits the exact payload field names createGroupv2 expects and emits created', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="group-form-website"]').setValue('https://example.com')
      await wrapper.find('[data-testid="group-form-email"]').setValue('info@example.com')
      await wrapper.find('[data-testid="group-form-phone"]').setValue('01234 567890')
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.createGroup).toHaveBeenCalledWith({
        name: 'Fixers United',
        website: 'https://example.com',
        email: 'info@example.com',
        phone: '01234 567890',
        description: '<p>We fix stuff</p>',
        location: 'Anytown',
        // Deliberately absent, not null: groups.postcode is NOT NULL in the
        // schema, and Laravel's ConvertEmptyStringsToNull middleware turns a
        // submitted '' into null before the server's own `input('postcode',
        // '')` default gets a chance to apply - so a blank postcode must be
        // omitted from the payload entirely (see GroupForm.vue's submit()).
        timezone: null,
      })
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('never shows the admin-only panel in create mode, even with isAdmin true', () => {
      const wrapper = mountForm({ isAdmin: true })
      expect(wrapper.find('[data-testid="group-form-networks"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-form-tags"]').exists()).toBe(false)
    })

    it('rejects an invalid website without hitting the store', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn()

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="group-form-website"]').setValue('not-a-url')
      await wrapper.find('[data-testid="group-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="group-form-website-error"]').exists()).toBe(true)
      expect(store.createGroup).not.toHaveBeenCalled()
    })

    it('renders server-side 422 field errors and a general error banner', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn().mockRejectedValue({
        status: 422,
        data: { errors: { name: ['That group name already exists.'] } },
      })

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-name-error"]').text()).toContain('already exists')
      expect(wrapper.find('[data-testid="group-form-error"]').exists()).toBe(true)
    })

    it('shows the image picker (gap: legacy GroupAddEdit.vue sets the photo at creation time)', () => {
      const wrapper = mountForm()
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(true)
    })

    it('uploads the selected image after createGroup resolves, in order, before emitting created', async () => {
      const store = useGroupsStore()
      const calls = []
      store.createGroup = vi.fn().mockImplementation(async () => {
        calls.push('createGroup')
        return 42
      })
      store.uploadGroupImage = vi.fn().mockImplementation(async (id, uploadKey) => {
        calls.push(['uploadGroupImage', id, uploadKey])
      })

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(calls).toEqual(['createGroup', ['uploadGroupImage', 42, 'key123']])
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('creates with no image without calling uploadGroupImage', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn().mockResolvedValue(42)
      store.uploadGroupImage = vi.fn()

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.createGroup).toHaveBeenCalled()
      expect(store.uploadGroupImage).not.toHaveBeenCalled()
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('still creates the group and emits created when the image upload fails after create', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn().mockResolvedValue(42)
      store.uploadGroupImage = vi.fn().mockRejectedValue(new Error('upload failed'))

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.uploadGroupImage).toHaveBeenCalledWith(42, 'key123')
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('shows an image upload error message from TusImageUpload', async () => {
      const wrapper = mountForm()
      await wrapper.find('[data-testid="stub-upload-fail"]').trigger('click')
      expect(wrapper.find('[data-testid="group-form-image-error"]').text()).toBe('boom')
    })
  })

  describe('edit mode', () => {
    const GROUP = {
      id: 5,
      name: 'Fixers United',
      website: 'https://example.com',
      email: 'info@example.com',
      phone: '0123',
      description: '<p>We fix stuff</p>',
      location: { location: 'Anytown', postcode: 'AB1 2CD', area: 'Anyshire', lat: 1, lng: 2 },
      timezone: 'Europe/London',
      approved: true,
      networks: [{ id: 1, name: 'UK Network' }],
      tags: [{ id: 9, name: 'Repair Café' }],
      network_data: { dummy: 'value' },
    }

    it('prefills fields from initialGroup', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP })
      expect(wrapper.find('[data-testid="group-form-name"]').element.value).toBe('Fixers United')
      expect(wrapper.find('[data-testid="location-picker-input"]').element.value).toBe('Anytown')
    })

    it('submits base fields plus round-tripped network_data with no admin permissions', async () => {
      const store = useGroupsStore()
      store.updateGroup = vi.fn().mockResolvedValue(5)

      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: false }, isAdmin: false })
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.updateGroup).toHaveBeenCalledWith(5, {
        name: 'Fixers United',
        website: 'https://example.com',
        email: 'info@example.com',
        phone: '0123',
        description: '<p>We fix stuff</p>',
        location: 'Anytown',
        postcode: 'AB1 2CD',
        timezone: 'Europe/London',
        network_data: JSON.stringify({ dummy: 'value' }),
      })
      expect(wrapper.emitted('updated')).toEqual([[5]])
      expect(wrapper.find('[data-testid="group-form-networks"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-form-tags"]').exists()).toBe(false)
    })

    it('shows and submits area/tags/moderate when can_demote is true', async () => {
      const store = useGroupsStore()
      store.updateGroup = vi.fn().mockResolvedValue(5)
      store.fetchTags = vi.fn().mockResolvedValue([])
      store.tags.data = [{ id: 9, name: 'Repair Café', network_name: null }]

      const unapproved = { ...GROUP, approved: false }
      const wrapper = mountForm({ groupId: 5, initialGroup: unapproved, permissions: { can_demote: true }, isAdmin: false })
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-area"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-form-moderate"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-form-area"]').setValue('New Area')
      await wrapper.find('[data-testid="group-form-moderate"]').setValue('approve')
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      const payload = store.updateGroup.mock.calls[0][1]
      expect(payload.area).toBe('New Area')
      expect(payload.moderate).toBe('approve')
      expect(payload.tags).toBe(JSON.stringify([9]))
    })

    it('shows and submits networks when isAdmin is true', async () => {
      const store = useGroupsStore()
      store.updateGroup = vi.fn().mockResolvedValue(5)

      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: false }, isAdmin: true })
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-networks"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      const payload = store.updateGroup.mock.calls[0][1]
      expect(payload.networks).toBe(JSON.stringify([1]))
    })

    it('does not show the moderate control once the group is already approved', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: true } })
      expect(wrapper.find('[data-testid="group-form-moderate"]').exists()).toBe(false)
    })

    it('does not render its own image picker (group/edit/[id].vue already renders one above the form)', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP })
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(false)
    })
  })

  describe('location map preview (gap 10)', () => {
    it('is hidden until lat/lng are set', () => {
      const wrapper = mountForm()
      expect(wrapper.find('[data-testid="group-form-map-preview"]').exists()).toBe(false)
    })

    it('shows a marker at the geocoded lat/lng once set', () => {
      const wrapper = mountForm({
        groupId: 5,
        initialGroup: { location: { location: 'Anytown', lat: 1, lng: 2 } },
      })
      const preview = wrapper.find('[data-testid="group-form-map-preview"]')
      expect(preview.exists()).toBe(true)

      const marker = wrapper.findComponent(LMarkerStub)
      expect(marker.exists()).toBe(true)
      expect(marker.props('latLng')).toEqual([1, 2])
    })
  })
})
