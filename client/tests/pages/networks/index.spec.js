import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworksIndexPage from '../../../app/pages/networks/index.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useSessionStore } from '../../../app/stores/session.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
}

function setLoggedInUser(user) {
  useSessionStore().user = user
  useAuthStore().user = user
  useAuthStore().token = 'tok-1'
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(NetworksIndexPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

const NETWORKS = [
  { id: 1, name: 'Test London', logo: null },
  { id: 2, name: 'Test Scotland', logo: null },
]

describe('pages/networks/index', () => {
  let networksStore
  let navigateToSpy

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ query: {}, params: {} }))
    navigateToSpy = vi.fn()
    vi.stubGlobal('navigateTo', navigateToSpy)

    networksStore = useNetworksStore()
    networksStore.fetchList = vi.fn().mockImplementation(async () => {
      networksStore.list.data = NETWORKS
    })
  })

  it('redirects Hosts/Restarters to /forbidden without fetching', () => {
    setLoggedInUser({ id: 1, role_name: 'Host', networks: [] })

    mountPage()

    expect(navigateToSpy).toHaveBeenCalledWith('/forbidden')
    expect(networksStore.fetchList).not.toHaveBeenCalled()
  })

  it('lets a NetworkCoordinator through without redirecting', () => {
    setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator', networks: [{ id: 1, name: 'Test London' }] })

    mountPage()

    expect(navigateToSpy).not.toHaveBeenCalled()
    expect(networksStore.fetchList).toHaveBeenCalled()
  })

  it('lets an Administrator through without redirecting', () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [] })

    mountPage()

    expect(navigateToSpy).not.toHaveBeenCalled()
    expect(networksStore.fetchList).toHaveBeenCalled()
  })

  it('shows only the networks the user coordinates under "Your networks"', async () => {
    setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator', networks: [{ id: 2, name: 'Test Scotland' }] })

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="your-network-row-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="your-network-row-1"]').exists()).toBe(false)
  })

  it('shows the "no networks" message when the user coordinates none', async () => {
    setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator', networks: [] })

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="your-networks-empty"]').exists()).toBe(true)
  })

  it('shows "All networks" for Administrators but not plain NetworkCoordinators', async () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [] })
    const adminWrapper = mountPage()
    await flushPromises()
    expect(adminWrapper.find('[data-testid="all-networks-section"]').exists()).toBe(true)
    expect(adminWrapper.find('[data-testid="all-network-row-1"]').exists()).toBe(true)
    expect(adminWrapper.find('[data-testid="all-network-row-2"]').exists()).toBe(true)

    setLoggedInUser({ id: 2, role_name: 'NetworkCoordinator', networks: [{ id: 1, name: 'Test London' }] })
    const ncWrapper = mountPage()
    await flushPromises()
    expect(ncWrapper.find('[data-testid="all-networks-section"]').exists()).toBe(false)
  })

  it('shows a real logo when present, and a generic placeholder when absent, in a hoverable fixed-layout table', async () => {
    networksStore.fetchList = vi.fn().mockImplementation(async () => {
      networksStore.list.data = [
        { id: 1, name: 'Test London', logo: 'https://example.com/logo.png' },
        { id: 2, name: 'Test Scotland', logo: null },
      ]
    })
    setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [{ id: 1, name: 'Test London' }, { id: 2, name: 'Test Scotland' }] })

    const wrapper = mountPage()
    await flushPromises()

    const table = wrapper.get('[data-testid="your-networks-table"]')
    expect(table.classes()).toContain('table-hover')
    expect(table.classes()).toContain('table-layout-fixed')

    const row1 = wrapper.get('[data-testid="your-network-row-1"]')
    expect(row1.get('img').attributes('src')).toBe('https://example.com/logo.png')

    const row2 = wrapper.get('[data-testid="your-network-row-2"]')
    expect(row2.get('img').attributes('src')).toBe('/images/placeholder-avatar.webp')
  })

  it('shows a load error with retry on failure', async () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [] })
    networksStore.fetchList = vi.fn().mockImplementation(async () => {
      networksStore.list.error = { status: 500 }
    })

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="networks-index-load-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="networks-index-retry"]').trigger('click')
    expect(networksStore.fetchList).toHaveBeenCalledTimes(2)
  })
})

async function flushPromises() {
  await Promise.resolve()
  await Promise.resolve()
}
