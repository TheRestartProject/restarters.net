import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DeviceSearchPage from '../../../app/pages/device/search.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DeviceSearchPage, {
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub } },
  })
}

describe('pages/device/search', () => {
  beforeEach(() => {
    setActivePinia(createPinia())

    vi.stubGlobal('useNuxtApp', () => ({
      $api: {
        device: {
          search: vi.fn().mockResolvedValue({ data: { items: [], count: 0 } }),
          itemTypes: vi.fn().mockResolvedValue({ data: [] }),
          categories: vi.fn().mockResolvedValue({ data: [] }),
          categoryClusters: vi.fn().mockResolvedValue({ data: [] }),
          brands: vi.fn().mockResolvedValue({ data: [] }),
          options: vi.fn().mockResolvedValue({ data: { barriers: [], spare_parts: [], next_steps: [] } }),
        },
      },
    }))
  })

  it('renders the page heading and the devices search table', async () => {
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-page"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="devices-search-table"]').exists()).toBe(true)
  })
})
