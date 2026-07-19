import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupViewPage from '../../../app/pages/group/view/[id].vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BBadge: BBadgeStub,
  BModal: BModalStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
}

const BASE_GROUP = {
  id: 5,
  name: 'Fixers United',
  image: null,
  website: 'https://example.com',
  description: '<p>We fix things.</p>',
  location: { location: 'Anytown' },
  tags: [],
  archived_at: null,
  permissions: {
    can_edit: false,
    can_demote: false,
    can_see_delete: false,
    can_perform_delete: false,
    can_perform_archive: false,
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupViewPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/group/view/[id]', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/group/view/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    groupsStore = useGroupsStore()
    groupsStore.fetchCurrent = vi.fn().mockResolvedValue(BASE_GROUP)
    groupsStore.fetchStats = vi.fn().mockResolvedValue(null)
    groupsStore.fetchEvents = vi.fn().mockResolvedValue([])
    groupsStore.fetchVolunteers = vi.fn().mockResolvedValue([])
    groupsStore.deleteGroup = vi.fn().mockResolvedValue({ archived: true })
  })

  it('fetches the group, stats, events and volunteers for the routed id on mount', () => {
    mountPage()

    expect(groupsStore.fetchCurrent).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchStats).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchEvents).toHaveBeenCalledWith(5)
    expect(groupsStore.fetchVolunteers).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton and no header while loading', () => {
    groupsStore.current.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-view-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-view-header"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls the fetches again', async () => {
    groupsStore.current.error = { status: 404 }

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-view-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-view-retry"]').trigger('click')
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  it('renders the header: image class hook, name, location and website', () => {
    groupsStore.current.data = BASE_GROUP

    const wrapper = mountPage()

    expect(wrapper.find('img.groupImage').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-view-name"]').text()).toBe('Fixers United')
    expect(wrapper.find('[data-testid="group-view-location"]').text()).toBe('Anytown')
    expect(wrapper.find('[data-testid="group-view-website"]').exists()).toBe(true)
  })

  describe('permission-gated buttons', () => {
    it('shows Edit only when can_edit is true', () => {
      groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-view-edit"]').exists()).toBe(true)

      groupsStore.current.data = BASE_GROUP
      const wrapperNoEdit = mountPage()
      expect(wrapperNoEdit.find('[data-testid="group-view-edit"]').exists()).toBe(false)
    })

    it('hides Archive entirely when can_perform_archive is false', () => {
      groupsStore.current.data = BASE_GROUP
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-view-archive"]').exists()).toBe(false)
    })

    it('shows Archive (not Delete) for a coordinator/admin when can_perform_archive is true', async () => {
      groupsStore.current.data = {
        ...BASE_GROUP,
        permissions: { ...BASE_GROUP.permissions, can_perform_archive: true },
      }
      const wrapper = mountPage()

      const button = wrapper.find('[data-testid="group-view-archive"]')
      expect(button.exists()).toBe(true)
      // labelled Archive, not Delete - it hits the reversible archive endpoint
      expect(button.text()).toBe('Archive group')
      expect(wrapper.find('[data-testid="group-view-delete"]').exists()).toBe(false)

      await button.trigger('click')
      expect(wrapper.find('[data-testid="group-view-archive-confirm"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-view-archive-confirm"]').trigger('click')
      expect(groupsStore.deleteGroup).toHaveBeenCalledWith(5)
    })

    it('shows Invite when can_edit is true even if not a member', () => {
      groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
      groupsStore.memberIds = []
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-view-invite"]').exists()).toBe(true)
    })

    it('shows Invite when a member even without can_edit', () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = [5]
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-view-invite"]').exists()).toBe(true)
    })

    it('hides Invite when neither can_edit nor a member', () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = []
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-view-invite"]').exists()).toBe(false)
    })
  })

  describe('join/leave integration', () => {
    it('shows a join button wired to the store when not a member', async () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = []
      groupsStore.join = vi.fn().mockResolvedValue()

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-join-5"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-join-5"]').trigger('click')
      expect(groupsStore.join).toHaveBeenCalledWith(5)
    })

    it('shows a leave button wired to the store when a member', async () => {
      groupsStore.current.data = BASE_GROUP
      groupsStore.memberIds = [5]
      groupsStore.leave = vi.fn().mockResolvedValue()

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="group-leave-5"]').exists()).toBe(true)

      await wrapper.find('[data-testid="group-leave-5"]').trigger('click')
      expect(groupsStore.leave).toHaveBeenCalledWith(5)
    })
  })

  it('opens the invite modal from the invite button', async () => {
    groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: true } }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-invite-form"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-view-invite"]').trigger('click')
    expect(wrapper.find('[data-testid="group-invite-form"]').exists()).toBe(true)
  })
})
