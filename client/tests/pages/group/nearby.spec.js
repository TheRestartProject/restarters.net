import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupNearbyPage from '../../../app/pages/group/nearby.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupNearbyPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub },
    },
  })
}

describe('pages/group/nearby', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNearby = vi.fn().mockResolvedValue([])
  })

  it('calls groupsStore.fetchNearby() on mount', () => {
    mountPage()
    expect(groupsStore.fetchNearby).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.nearby.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-list"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchNearby again', async () => {
    groupsStore.nearby.error = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-nearby-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-nearby-retry"]').trigger('click')
    expect(groupsStore.fetchNearby).toHaveBeenCalledTimes(2)
  })

  it('shows the no-groups-nearby empty state when there are no nearby groups', () => {
    groupsStore.nearby.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-no-location"]').exists()).toBe(false)
  })

  it('shows the set-a-location prompt (not the plain empty state) when the user has no town set', () => {
    // Legacy's "Other groups nearby" tab distinguishes "no location set" from
    // "no groups nearby"; hasLocation comes from the dashboard's has_location.
    const dashboardStore = useDashboardStore()
    dashboardStore.data = { has_location: false }
    groupsStore.nearby.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-no-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-empty"]').exists()).toBe(false)
  })

  it('renders a card per nearby group', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-card-5"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-join-5"]').exists()).toBe(true)
  })

  it('reflects membership from the store on the join button', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]
    groupsStore.memberIds = [5]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-leave-5"]').exists()).toBe(true)
  })
})
