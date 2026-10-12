import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FixometerPage from '../../app/pages/fixometer.vue'
import { useFixometerStore } from '../../app/stores/fixometer.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
// DevicesSearchTable is mounted for real by DevicesSearchTable.spec.js -
// stubbed here so this page-level suite doesn't also need a working $api
// mock for its onMounted() searches/metadata fetches.
const DevicesSearchTableStub = { template: '<div data-testid="devices-search-table-stub" />' }
// AddDataModal.spec.js covers its own fetch/render logic - stubbed here so
// this page-level suite doesn't also need dashboard/events $api mocks.
const AddDataModalStub = { props: ['show'], template: '<div v-if="show" data-testid="add-data-modal-stub" />' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(FixometerPage, {
    global: {
      plugins: [i18n],
      stubs: {
        NuxtLink: NuxtLinkStub,
        BAlert: BAlertStub,
        BButton: BButtonStub,
        DevicesSearchTable: DevicesSearchTableStub,
        AddDataModal: AddDataModalStub,
      },
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

  it('renders the impact stats and the embedded repair-records table', async () => {
    fixometerStore.impactData.data = { participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100 }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="impact-stats"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="devices-search-table-stub"]').exists()).toBe(true)
  })

  // Gap fix: a prior version also rendered a "Browse repair records" link
  // through to /device/search here - legacy's FixometerPage.vue header row
  // has only the (Administrator-only) export button; browsing already
  // happens in place, via the embedded table above.
  it('does not render a separate "browse records" link - the embedded table is the browse UI', async () => {
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="fixometer-browse-records"]').exists()).toBe(false)
  })

  // Gap fix (HIGH): legacy's "Add Data" button is unconditional (rendered
  // even logged-out) and opens AddDataModal.vue - a prior version gated it
  // on `loggedIn` and linked straight to /dashboard instead.
  it('shows the "Add Data" button unconditionally (even logged-out) and opens the picker modal', async () => {
    const wrapper = mountPage()
    await flushPromises()

    const button = wrapper.find('[data-testid="fixometer-add-data"]')
    expect(button.exists()).toBe(true)
    expect(wrapper.find('[data-testid="add-data-modal-stub"]').exists()).toBe(false)

    await button.trigger('click')
    expect(wrapper.find('[data-testid="add-data-modal-stub"]').exists()).toBe(true)
  })

  it('offers everyone the repair data download, the built file when there is one', async () => {
    fixometerStore.impactData.data = {
      participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100,
      download_url: '/exports/repair-data.csv',
    }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()

    const exportLink = wrapper.find('[data-testid="fixometer-export-devices"]')
    expect(exportLink.exists()).toBe(true)
    expect(exportLink.attributes('href')).toBe('/exports/repair-data.csv')
    expect(exportLink.classes()).toContain('btn-primary')
    expect(exportLink.classes()).not.toContain('btn-outline-primary')
  })

  it('falls back to the live export when the data has no download url', async () => {
    fixometerStore.impactData.data = { participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100 }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="fixometer-export-devices"]').attributes('href')).toBe('/export/devices')
  })

  it('links to further reports when the install has them', async () => {
    fixometerStore.impactData.data = {
      participants: 5, hours_volunteered: 100, waste_total: 100, co2_total: 100,
      reporting_url: 'https://reports.example.org/all',
    }
    fixometerStore.impactData.loaded = true

    const wrapper = mountPage()
    await flushPromises()

    const link = wrapper.find('[data-testid="impact-stats-reporting-link"]')
    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('https://reports.example.org/all')
    expect(link.attributes('target')).toBe('_blank')
  })
})
