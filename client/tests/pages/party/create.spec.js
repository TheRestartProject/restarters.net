import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventCreatePage from '../../../app/pages/party/create/[[group_id]].vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const EventFormStub = {
  props: ['groups', 'isAdmin', 'initialGroupId'],
  emits: ['created'],
  template: '<button data-testid="stub-create" @click="$emit(\'created\', 77)" />',
}

const GLOBAL_STUBS = {
  BAlert: BAlertStub,
  BButton: BButtonStub,
  EventForm: EventFormStub,
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventCreatePage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/party/create/[[group_id]]', () => {
  let groupsStore
  let authStore
  let myGroups

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: {}, query: {}, fullPath: '/party/create' }))
    vi.stubGlobal('navigateTo', vi.fn())

    myGroups = vi.fn().mockResolvedValue({ data: [] })
    vi.stubGlobal('useNuxtApp', () => ({ $api: { user: { myGroups } } }))

    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])

    authStore = useAuthStore()
    authStore.user = { role_name: 'Host' }
  })

  it('renders the page test hook and heading', async () => {
    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="party-create-page"]').exists()).toBe(true)
    expect(wrapper.find('h1').text()).toBe('Add new event')
  })

  it('shows a loading skeleton while the groups list is in flight', () => {
    let resolve
    myGroups.mockReturnValueOnce(new Promise((r) => { resolve = r }))

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="party-create-loading"]').exists()).toBe(true)
    resolve({ data: [] })
  })

  it('shows an error state with a retry button when the groups fetch fails', async () => {
    myGroups.mockRejectedValueOnce(new Error('boom'))

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="party-create-groups-error"]').exists()).toBe(true)

    myGroups.mockResolvedValueOnce({ data: [] })
    await wrapper.find('[data-testid="party-create-retry"]').trigger('click')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(myGroups).toHaveBeenCalledTimes(2)
  })

  it('shows the cantcreate page for a Restarter', async () => {
    authStore.user = { role_name: 'Restarter' }
    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="party-cantcreate"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-create"]').exists()).toBe(false)
  })

  it('shows the cantcreate page for a Host who hosts no groups', async () => {
    authStore.user = { role_name: 'Host' }
    myGroups.mockResolvedValueOnce({ data: [] })

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="party-cantcreate"]').exists()).toBe(true)
  })

  it('shows the form for a Host who hosts at least one group, sourced from users/me/groups filtered to HOST role', async () => {
    authStore.user = { role_name: 'Host' }
    myGroups.mockResolvedValueOnce({
      data: [
        { id: 9, name: 'Acme Restarters', role: 3, archived: false },
        { id: 10, name: 'Old Group', role: 3, archived: true },
        { id: 11, name: 'Not Hosted', role: 4, archived: false },
      ],
    })

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    const form = wrapper.findComponent(EventFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('groups')).toEqual([{ id: 9, name: 'Acme Restarters' }])
    expect(form.props('isAdmin')).toBe(false)
    expect(groupsStore.fetchNames).not.toHaveBeenCalled()
  })

  it('shows the form for an Administrator, sourced from every non-archived group', async () => {
    authStore.user = { role_name: 'Administrator' }
    groupsStore.names = [
      { id: 1, name: 'Group A', archived_at: null },
      { id: 2, name: 'Archived Group', archived_at: '2020-01-01' },
    ]

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    const form = wrapper.findComponent(EventFormStub)
    expect(form.props('groups')).toEqual([{ id: 1, name: 'Group A' }])
    expect(form.props('isAdmin')).toBe(true)
    expect(myGroups).not.toHaveBeenCalled()
  })

  it('passes group_id from the route as initialGroupId', async () => {
    vi.stubGlobal('useRoute', () => ({ params: { group_id: '9' }, query: {}, fullPath: '/party/create/9' }))
    myGroups.mockResolvedValueOnce({ data: [{ id: 9, name: 'Acme Restarters', role: 3, archived: false }] })

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(EventFormStub).props('initialGroupId')).toBe(9)
  })

  it('navigates to /party/edit/{id} when EventForm emits created', async () => {
    myGroups.mockResolvedValueOnce({ data: [{ id: 9, name: 'Acme Restarters', role: 3, archived: false }] })
    const navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    await wrapper.find('[data-testid="stub-create"]').trigger('click')

    expect(navigateToMock).toHaveBeenCalledWith('/party/edit/77')
  })
})
