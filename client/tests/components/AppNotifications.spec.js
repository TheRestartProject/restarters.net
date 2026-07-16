import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import AppNotifications from '../../app/components/AppNotifications.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(AppNotifications, {
    global: { plugins: [i18n] },
  })
}

describe('components/AppNotifications', () => {
  let mockApi
  let originalHref

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()

    useAuthStore().user = { id: 42, username: 'jane' }
    useSessionStore().config = { discourse_url: 'https://talk.example.com' }

    mockApi = {
      user: { notifications: vi.fn() },
      auth: { ssoTicket: vi.fn() },
    }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))

    originalHref = window.location.href
    delete window.location
    window.location = { href: '' }
  })

  afterEach(() => {
    vi.useRealTimers()
    window.location = { href: originalHref }
  })

  it('fetches counts on mount and renders them', async () => {
    mockApi.user.notifications.mockResolvedValue({ success: 'success', restarters: 2, discourse: 5 })

    const wrapper = mountComponent()
    await vi.waitFor(() => expect(mockApi.user.notifications).toHaveBeenCalledTimes(1))
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="notifications-discourse-count"]').text()).toBe('5')
    expect(wrapper.find('[data-testid="notifications-restarters-count"]').text()).toBe('2')
  })

  it('shows "--" for both counts before the first fetch resolves', () => {
    mockApi.user.notifications.mockReturnValue(new Promise(() => {}))

    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="notifications-discourse-count"]').text()).toBe('--')
    expect(wrapper.find('[data-testid="notifications-restarters-count"]').text()).toBe('--')
  })

  it('polls again after the interval elapses', async () => {
    mockApi.user.notifications.mockResolvedValue({ success: 'success', restarters: 0, discourse: 0 })

    mountComponent()
    await vi.waitFor(() => expect(mockApi.user.notifications).toHaveBeenCalledTimes(1))

    await vi.advanceTimersByTimeAsync(60000)
    expect(mockApi.user.notifications).toHaveBeenCalledTimes(2)

    await vi.advanceTimersByTimeAsync(60000)
    expect(mockApi.user.notifications).toHaveBeenCalledTimes(3)
  })

  it('clicking the restarters badge toggles the panel', async () => {
    mockApi.user.notifications.mockResolvedValue({ success: 'success', restarters: 3, discourse: 0 })

    const wrapper = mountComponent()
    await vi.waitFor(() => expect(mockApi.user.notifications).toHaveBeenCalledTimes(1))
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="notifications-restarters-panel"]').exists()).toBe(false)

    await wrapper.find('[data-testid="notifications-restarters-badge"]').trigger('click')
    expect(wrapper.find('[data-testid="notifications-restarters-panel"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notifications-restarters-text"]').text()).toContain('3')
  })

  it('clicking the discourse badge routes through the SSO bridge to Discourse\'s notifications page', async () => {
    mockApi.user.notifications.mockResolvedValue({ success: 'success', restarters: 0, discourse: 1 })
    mockApi.auth.ssoTicket.mockResolvedValueOnce({
      data: { ticket: 'tok-1', bridge_url: 'http://localhost:8001/auth/bridge' },
    })

    const wrapper = mountComponent()
    await vi.waitFor(() => expect(mockApi.user.notifications).toHaveBeenCalledTimes(1))

    await wrapper.find('[data-testid="notifications-discourse-badge"]').trigger('click')
    await vi.waitFor(() => expect(mockApi.auth.ssoTicket).toHaveBeenCalledTimes(1))

    const expectedRedirect =
      'https://talk.example.com/session/sso?return_path=' +
      encodeURIComponent('https://talk.example.com/u/jane/notifications')
    expect(window.location.href).toBe(
      `http://localhost:8001/auth/bridge?ticket=tok-1&redirect=${encodeURIComponent(expectedRedirect)}`
    )
  })
})
