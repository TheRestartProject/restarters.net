import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkEditPage from '../../../app/pages/networks/[id]/edit.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const STUBS = {
  NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' },
  BAlert: { template: '<div v-bind="$attrs"><slot /></div>' },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  TusImageUpload: { template: '<div data-testid="stub-upload" />' },
}

const NETWORK = { id: 1, name: 'Test London', logo: null, reporting_url: 'https://reports.example.org/dashboard/7' }

function setUser(user) {
  useSessionStore().user = user
  useAuthStore().user = user
  useAuthStore().token = 'tok-1'
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(NetworkEditPage, { global: { plugins: [i18n], stubs: STUBS } })
}

describe('pages/networks/[id]/edit', () => {
  let networksStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '1' }, query: {}, fullPath: '/networks/1/edit' }))
    vi.stubGlobal('useHead', vi.fn())

    networksStore = useNetworksStore()
    networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
      networksStore.current.data = NETWORK
    })
    networksStore.update = vi.fn().mockResolvedValue({ id: 1, reporting_url: 'https://reports.example.org/dashboard/9' })
  })

  it('lets an administrator see and change the reporting address', async () => {
    setUser({ id: 1, role_name: 'Administrator', networks: [] })

    const wrapper = mountPage()
    await flushPromises()

    const input = wrapper.find('[data-testid="network-edit-reporting-url"]')
    expect(input.element.value).toBe('https://reports.example.org/dashboard/7')

    await input.setValue('  https://reports.example.org/dashboard/9 ')
    await wrapper.find('[data-testid="network-edit-reporting"]').trigger('submit')
    await flushPromises()

    expect(networksStore.update).toHaveBeenCalledWith(1, { reporting_url: 'https://reports.example.org/dashboard/9' })
    expect(wrapper.find('[data-testid="network-edit-reporting-saved"]').exists()).toBe(true)
  })

  it('sends a blank address to clear it', async () => {
    setUser({ id: 1, role_name: 'Administrator', networks: [] })

    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="network-edit-reporting-url"]').setValue('')
    await wrapper.find('[data-testid="network-edit-reporting"]').trigger('submit')
    await flushPromises()

    expect(networksStore.update).toHaveBeenCalledWith(1, { reporting_url: '' })
  })

  it('shows an error when the address is refused', async () => {
    setUser({ id: 1, role_name: 'Administrator', networks: [] })
    networksStore.update = vi.fn().mockRejectedValue(new Error('422'))

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="network-edit-reporting"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="network-edit-reporting-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-edit-reporting-saved"]').exists()).toBe(false)
  })

  it('does not offer the field to a network coordinator', async () => {
    setUser({ id: 2, role_name: 'NetworkCoordinator', networks: [{ id: 1, name: 'Test London' }] })

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="network-edit-page"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-edit-reporting"]').exists()).toBe(false)
  })
})
