import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FixometerPage from '../../app/pages/fixometer.vue'
import { useFixometerStore } from '../../app/stores/fixometer.js'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
// DevicesSearchTable is mounted for real by DevicesSearchTable.spec.js -
// stubbed here so this page-level suite doesn't also need a working $api
// mock for its onMounted() searches/metadata fetches.
const DevicesSearchTableStub = { template: '<div data-testid="devices-search-table-stub" />' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(FixometerPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, DevicesSearchTable: DevicesSearchTableStub },
    },
  })
}

describe('pages/fixometer', () => {
  let fixometerStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRuntimeConfig', () => ({ public: {} }))

    fixometerStore = useFixometerStore()
    fixometerStore.fetchImpactData = vi.fn().mockResolvedValue({})
    fixometerStore.fetchLatestRepaired = vi.fn().mockResolvedValue(null)
  })

  it('fetches impact data and the latest-repaired-event banner on mount', () => {
    mountPage()
    expect(fixometerStore.fetchImpactData).toHaveBeenCalledTimes(1)
    expect(fixometerStore.fetchLatestRepaired).toHaveBeenCalledTimes(1)
  })

  it('shows an error state with a retry button when impact data fails to load', async () => {
    fixometerStore.impactData.error = { status: 500 }

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="fixometer-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="fixometer-retry"]').trigger('click')
    expect(fixometerStore.fetchImpactData).toHaveBeenCalledWith({ force: true })
    expect(fixometerStore.fetchLatestRepaired).toHaveBeenCalledWith({ force: true })
  })

  it('renders the impact stats, the embedded repair-records table, and a link through to the standalone device search page', async () => {
    fixometerStore.impactData.data = { participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100 }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="impact-stats"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="devices-search-table-stub"]').exists()).toBe(true)
    const link = wrapper.find('[data-testid="fixometer-browse-records"]')
    expect(link.attributes('href')).toBe('/device/search')
  })

  it('only shows the "Add Data" button when logged in', async () => {
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('[data-testid="fixometer-add-data"]').exists()).toBe(false)

    const authStore = useAuthStore()
    authStore.token = 'a-token'
    authStore.user = { id: 1, role_name: 'Restarter' }
    await flushPromises()

    expect(wrapper.find('[data-testid="fixometer-add-data"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="fixometer-add-data"]').attributes('href')).toBe('/dashboard')
  })

  it('only shows the "Download all data" export link for Administrators', async () => {
    fixometerStore.impactData.data = { participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100 }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('[data-testid="fixometer-export-devices"]').exists()).toBe(false)

    const authStore = useAuthStore()
    authStore.token = 'a-token'
    authStore.user = { id: 1, role_name: 'Administrator' }
    await flushPromises()

    const exportLink = wrapper.find('[data-testid="fixometer-export-devices"]')
    expect(exportLink.exists()).toBe(true)
    expect(exportLink.attributes('href')).toBe('/export/devices')
  })
})
