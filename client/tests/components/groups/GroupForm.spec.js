import { flushPromises, mount } from '@vue/test-utils'
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
    '<input data-testid="location-picker-input" :value="location" :data-can-edit-postcode="canEditPostcode" @input="$emit(\'update:location\', $event.target.value)" />',
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
  props: { currentImageUrl: String, compact: Boolean },
  emits: ['uploaded', 'upload-error'],
  template:
    '<div data-testid="stub-tus-image-upload"><button data-testid="stub-upload-ok" @click="$emit(\'uploaded\', { uploadKey: \'key123\' })" /><button data-testid="stub-upload-fail" @click="$emit(\'upload-error\', \'boom\')" /></div>',
}
// Stands in for GroupMultiSelect.vue (unit-tested on its own) - a button per
// option is enough to drive GroupForm's payload/permission-gating tests
// without re-testing the search/chip/grouping UI here.
const GroupMultiSelectStub = {
  props: ['modelValue', 'options', 'testid'],
  emits: ['update:modelValue'],
  template:
    '<div :data-testid="testid"><button v-for="o in options" :key="o.value" :data-testid="`${testid}-select-${o.value}`" @click="$emit(\'update:modelValue\', [...modelValue, o.value])" /></div>',
}
const GroupNetworkDataStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<div data-testid="stub-group-network-data" />',
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
  TusImageUpload: TusImageUploadStub,
  GroupMultiSelect: GroupMultiSelectStub,
  GroupNetworkData: GroupNetworkDataStub,
}

// groups.duplicate and networks.edit.* are new copy (findings/parity-v2/
// group-forms.md #5 and #7) added to lang/en/groups.php and
// lang/en/networks.php, but the generated client/i18n/locales/en.json is
// regenerated centrally via `php artisan translations:export-client` - so
// they're supplied inline here rather than by editing the checked-in
// locale file, exactly matching the copy that will land there.
const LANG_OVERRIDES = {
  groups: {
    duplicate: 'That group name ({name}) already exists.  If it\'s yours, please go to the Groups page using the menu and edit it.',
  },
  networks: {
    edit: {
      add_new_field: 'Add new field',
      new_field_name: 'New field name',
      add_field: 'Add field',
    },
  },
}

function mountForm(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        groups: { ...en.groups, ...LANG_OVERRIDES.groups },
        networks: { ...en.networks, edit: { ...en.networks?.edit, ...LANG_OVERRIDES.networks.edit } },
      },
    },
  })

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

    it('renders server-side 422 field errors and a general error message', async () => {
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
      // gap 10: plain bold-red text next to the submit button, not a boxed
      // alert at the top of the form.
      expect(wrapper.find('[data-testid="group-form-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-form-error"]').classes()).toContain('text-danger')
    })

    it('shows the image picker (legacy GroupAddEdit.vue sets the photo at creation time)', () => {
      const wrapper = mountForm()
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(true)
    })

    it('uses the compact 100x100 thumbnail picker, matching legacy GroupImage.vue (gap 13)', () => {
      const wrapper = mountForm()
      expect(wrapper.findComponent(TusImageUploadStub).props('compact')).toBe(true)
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

    it('keeps the postcode read-only, regardless of role (gap 12: no `|| creating` override)', () => {
      const wrapper = mountForm({ isAdmin: true })
      expect(wrapper.find('[data-testid="location-picker-input"]').attributes('data-can-edit-postcode')).toBe('false')
    })

    it('shows the groups_approval_text next to the submit button, not in a page header (gap 9)', () => {
      const wrapper = mountForm()
      const buttons = wrapper.find('[data-testid="group-form-buttons"]')
      expect(buttons.text()).toContain('Group submissions need to be approved by an administrator')
    })
  })

  describe('duplicate name check (gap 7)', () => {
    it('shows a bold red warning under the Name field and blocks submission', async () => {
      const store = useGroupsStore()
      store.createGroup = vi.fn()
      store.names = [{ id: 9, name: 'Fixers United', archived_at: null }]

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.vm.$nextTick()

      const warning = wrapper.find('[data-testid="group-form-duplicate-name"]')
      expect(warning.exists()).toBe(true)
      expect(warning.text()).toContain('Fixers United')

      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      expect(store.createGroup).not.toHaveBeenCalled()
    })

    it('does not warn when the only name match is the group being edited', async () => {
      const store = useGroupsStore()
      store.updateGroup = vi.fn().mockResolvedValue(5)
      store.names = [{ id: 5, name: 'Fixers United', archived_at: null }]

      const wrapper = mountForm({ groupId: 5, initialGroup: { name: 'Fixers United', description: '<p>x</p>', location: { location: 'Anytown' } } })
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-duplicate-name"]').exists()).toBe(false)
    })

    it('does not warn when the name is unique', async () => {
      const store = useGroupsStore()
      store.names = [{ id: 9, name: 'Someone Else', archived_at: null }]

      const wrapper = mountForm()
      await fillRequiredFields(wrapper)
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-duplicate-name"]').exists()).toBe(false)
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
      store.tags.data = [{ id: 9, name: 'Repair Café', network_id: null, network_name: null }]

      const unapproved = { ...GROUP, approved: false }
      const wrapper = mountForm({ groupId: 5, initialGroup: unapproved, permissions: { can_demote: true }, isAdmin: false })
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-area"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-form-moderate"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-form-area"]').setValue('New Area')
      await wrapper.find('[data-testid="group-form-moderate"]').setValue('approve')
      // GROUP.tags already includes id 9 (see initialGroup below), so
      // form.tagIds starts as [9] - just verifying it round-trips through
      // to the payload here, not re-selecting it.
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
      await flushPromises()

      expect(wrapper.find('[data-testid="group-form-networks"]').exists()).toBe(true)

      // GROUP.networks already includes id 1, so networkIds starts as [1] -
      // submitting without any further interaction is enough to check it
      // round-trips through to the payload.
      await wrapper.find('[data-testid="group-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      const payload = store.updateGroup.mock.calls[0][1]
      expect(payload.networks).toBe(JSON.stringify([1]))
    })

    it('does not show the moderate control once the group is already approved', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: true } })
      expect(wrapper.find('[data-testid="group-form-moderate"]').exists()).toBe(false)
    })

    it('renders the network-data editor for admins/moderators (gap 5)', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: true } })
      expect(wrapper.findComponent(GroupNetworkDataStub).exists()).toBe(true)
      expect(wrapper.findComponent(GroupNetworkDataStub).props('modelValue')).toEqual({ dummy: 'value' })
    })

    it('shows the image picker in edit mode too (gap 8: repositioned, not removed)', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP })
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(true)
    })

    it('uploads immediately (not deferred) when an image is picked in edit mode', async () => {
      const store = useGroupsStore()
      store.uploadGroupImage = vi.fn().mockResolvedValue({ image_url: '/uploads/new.png' })

      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP })
      await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')

      expect(store.uploadGroupImage).toHaveBeenCalledWith(5, 'key123')
    })

    it('shows an image upload error message when the immediate edit-mode upload fails', async () => {
      const store = useGroupsStore()
      store.uploadGroupImage = vi.fn().mockRejectedValue(new Error('boom'))

      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP })
      await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-form-image-error"]').exists()).toBe(true)
    })

    it('allows postcode edits for moderators (gap 12)', () => {
      const wrapper = mountForm({ groupId: 5, initialGroup: GROUP, permissions: { can_demote: true } })
      expect(wrapper.find('[data-testid="location-picker-input"]').attributes('data-can-edit-postcode')).toBe('true')
    })
  })

  describe('location map preview (gap 10 / gap 15)', () => {
    it('is hidden until lat/lng are set', () => {
      const wrapper = mountForm()
      expect(wrapper.find('[data-testid="group-form-map-preview"]').exists()).toBe(false)
    })

    it('shows a marker at the geocoded lat/lng once set, zoomed to 11 (matching GroupLocationMap.vue)', () => {
      const wrapper = mountForm({
        groupId: 5,
        initialGroup: { location: { location: 'Anytown', lat: 1, lng: 2 } },
      })
      const preview = wrapper.find('[data-testid="group-form-map-preview"]')
      expect(preview.exists()).toBe(true)

      const map = wrapper.findComponent(LMapStub)
      expect(map.props('zoom')).toBe(11)

      const marker = wrapper.findComponent(LMarkerStub)
      expect(marker.exists()).toBe(true)
      expect(marker.props('latLng')).toEqual([1, 2])
    })
  })
})
