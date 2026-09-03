import { mount, flushPromises } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import AlertsBanner from '../../../app/components/alerts/AlertsBanner.vue'

const BAlertStub = {
  props: ['modelValue', 'variant'],
  emits: ['dismissed'],
  template: '<div class="alert" @click="$emit(\'dismissed\')"><slot /></div>',
}

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: {} } })

  return mount(AlertsBanner, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub },
    },
  })
}

function alert(overrides = {}) {
  return {
    id: 1,
    title: 'Support us',
    html: '<p>Please donate</p>',
    ctatitle: null,
    ctalink: null,
    start: '2020-01-01T00:00:00Z',
    end: '2099-01-01T00:00:00Z',
    variant: 'secondary',
    ...overrides,
  }
}

describe('components/alerts/AlertsBanner', () => {
  let listResponse

  beforeEach(() => {
    localStorage.clear()
    listResponse = { data: [] }
    vi.stubGlobal('useNuxtApp', () => ({
      $api: { alerts: { list: vi.fn(() => Promise.resolve(listResponse)) } },
    }))
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    localStorage.clear()
  })

  it('renders nothing when there are no alerts', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alerts-banner"]').exists()).toBe(false)
  })

  it('renders an alert from the API with its title, html body and CTA button', async () => {
    listResponse = {
      data: [alert({ id: 5, title: 'Big news', html: '<p>Something <b>important</b></p>', ctatitle: 'Donate now', ctalink: 'https://example.com/donate' })],
    }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alerts-banner"]').exists()).toBe(true)
    const banner = wrapper.find('[data-testid="alert-banner-5"]')
    expect(banner.exists()).toBe(true)
    expect(banner.text()).toContain('Big news')
    expect(banner.html()).toContain('<b>important</b>')

    const cta = banner.find('a')
    expect(cta.attributes('href')).toBe('https://example.com/donate')
    expect(cta.text()).toBe('Donate now')
  })

  it('does not render a CTA button when the alert has no ctatitle/ctalink', async () => {
    listResponse = { data: [alert({ id: 6 })] }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alert-banner-6"]').find('a').exists()).toBe(false)
  })

  it('excludes alerts outside their start/end window even if the (cached) API returns them', async () => {
    listResponse = { data: [alert({ id: 7, start: '2099-01-01T00:00:00Z', end: '2099-06-01T00:00:00Z' })] }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alerts-banner"]').exists()).toBe(false)
  })

  it('dismissing an alert removes it from the banner and remembers the dismissal in localStorage', async () => {
    listResponse = { data: [alert({ id: 8 })] }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alert-banner-8"]').exists()).toBe(true)

    await wrapper.find('[data-testid="alert-banner-8"]').trigger('click')

    expect(wrapper.find('[data-testid="alert-banner-8"]').exists()).toBe(false)
    expect(localStorage.getItem('alert-8')).toBeTruthy()
  })

  it('does not show an alert already dismissed in localStorage on a previous visit', async () => {
    localStorage.setItem('alert-9', 'true')
    listResponse = { data: [alert({ id: 9 })] }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alerts-banner"]').exists()).toBe(false)
  })

  it('degrades to no banner when the API call fails', async () => {
    vi.stubGlobal('useNuxtApp', () => ({
      $api: { alerts: { list: vi.fn(() => Promise.reject(new Error('nope'))) } },
    }))

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="alerts-banner"]').exists()).toBe(false)
  })
})
