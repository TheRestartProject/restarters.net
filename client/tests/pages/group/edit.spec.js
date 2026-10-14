import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupEditPage from '../../../app/pages/group/edit/[id].vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const GroupFormStub = {
  props: ['groupId', 'initialGroup', 'permissions', 'isAdmin'],
  emits: ['updated'],
  template: '<button data-testid="stub-updated" @click="$emit(\'updated\', groupId)" />',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  GroupForm: GroupFormStub,
}

const BASE_GROUP = {
  id: 5,
  name: 'Fixers United',
  image: null,
  permissions: {
    can_edit: true,
    can_demote: false,
    can_see_delete: false,
    can_perform_delete: false,
    can_perform_archive: false,
  },
}

function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn } },
  })

  return mount(GroupEditPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/group/edit/[id]', () => {
  let groupsStore
  let sessionStore
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/group/edit/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    // The Group log tab calls $api.group.audits(id) lazily on first open.
    mockApi = { group: { audits: vi.fn().mockResolvedValue({ data: [] }) } }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))

    groupsStore = useGroupsStore()
    groupsStore.fetchCurrent = vi.fn().mockResolvedValue(BASE_GROUP)

    sessionStore = useSessionStore()
    sessionStore.user = { role: 4 }
  })

  it('fetches the group for the routed id on mount', () => {
    mountPage()
    expect(groupsStore.fetchCurrent).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.current.loading = true
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-edit-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button', async () => {
    groupsStore.current.error = { status: 404 }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-edit-error"]').exists()).toBe(true)
    await wrapper.find('[data-testid="group-edit-retry"]').trigger('click')
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  it('shows a forbidden message instead of the form when can_edit is false', () => {
    groupsStore.current.data = { ...BASE_GROUP, permissions: { ...BASE_GROUP.permissions, can_edit: false } }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-edit-forbidden"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-updated"]').exists()).toBe(false)
  })

  it('renders the form pre-wired to the fetched group when can_edit is true', () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    const form = wrapper.findComponent(GroupFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('groupId')).toBe(5)
    expect(form.props('initialGroup')).toEqual(BASE_GROUP)
  })

  it('treats Root (role 1) and Administrator (role 2) as isAdmin, others as not', () => {
    groupsStore.current.data = BASE_GROUP

    sessionStore.user = { role: 2 }
    let wrapper = mountPage()
    expect(wrapper.findComponent(GroupFormStub).props('isAdmin')).toBe(true)

    sessionStore.user = { role: 4 }
    wrapper = mountPage()
    expect(wrapper.findComponent(GroupFormStub).props('isAdmin')).toBe(false)
  })

  it('shows a success banner and re-fetches when GroupForm emits updated', async () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-updated"]').trigger('click')

    expect(wrapper.find('[data-testid="group-edit-success"]').exists()).toBe(true)
    expect(groupsStore.fetchCurrent).toHaveBeenCalledTimes(2)
  })

  // gap 4: an always-present "Group details" tab strip above the form, so
  // the page's chrome matches develop even without a "Group log" audit tab
  // (no API for that - see this page's own doc comment).
  it('shows the always-present Group details tab', () => {
    groupsStore.current.data = BASE_GROUP
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-edit-tabs"]').text()).toContain('Group details')
  })

  // Matches legacy GroupAddEdit.vue exactly: no archive control on this
  // page, regardless of can_perform_archive - archiving is only reachable
  // from the group VIEW page's Group Actions dropdown (see
  // tests/pages/group/view.spec.js's archive coverage).
  it('never shows an archive control here, even when can_perform_archive is true', () => {
    groupsStore.current.data = {
      ...BASE_GROUP,
      permissions: { ...BASE_GROUP.permissions, can_perform_archive: true },
    }
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-edit-archive"]').exists()).toBe(false)
  })
  // group/edit.blade.php:11-15 - Group log tab, Administrator only, fetched
  // lazily so landing on the edit form does not hit the endpoint.
  describe('group log tab', () => {
    it('is hidden from a non-administrator', async () => {
      groupsStore.current.data = BASE_GROUP
      sessionStore.user = { role: 4 }
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="group-edit-tab-log"]').exists()).toBe(false)
    })

    it('shows for an administrator and fetches only on open', async () => {
      groupsStore.current.data = BASE_GROUP
      sessionStore.user = { role: 2 }
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="group-edit-tab-log"]').exists()).toBe(true)
      expect(mockApi.group.audits).not.toHaveBeenCalled()

      await wrapper.find('[data-testid="group-edit-tab-log"]').trigger('click')
      await flushPromises()

      expect(mockApi.group.audits).toHaveBeenCalledWith(5)
    })
  })

})
