import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import AppNotifications from '../../app/components/AppNotifications.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to" @click="$emit(\'click\')"><slot /></a>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(AppNotifications, {
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BButton: BButtonStub, teleport: true } },
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
      user: { notifications: vi.fn(), myNotifications: vi.fn(), markNotificationsRead: vi.fn() },
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

  const cards = [
    { id: 'a', type: 'JoinGroup', title: 'Joined', name: 'Group A', url: '/group/view/1', read: false, created_at: '2026-01-01T00:00:00Z' },
    { id: 'b', type: 'EventConfirmed', title: '', name: 'Event B', url: null, read: true, created_at: '2026-01-02T00:00:00Z' },
  ]

  async function openPanel(items) {
    mockApi.user.notifications.mockResolvedValue({ success: 'success', restarters: 1, discourse: 0 })
    mockApi.user.myNotifications.mockResolvedValue({ data: items, meta: {} })
    const wrapper = mountComponent()
    await vi.waitFor(() => expect(mockApi.user.notifications).toHaveBeenCalledTimes(1))
    expect(wrapper.find('[data-testid="notifications-restarters-panel"]').exists()).toBe(false)
    await wrapper.find('[data-testid="notifications-restarters-badge"]').trigger('click')
    await vi.waitFor(() => expect(mockApi.user.myNotifications).toHaveBeenCalledTimes(1))
    await flushPromises()
    return wrapper
  }

  it('renders live\'s #notifications aside with scroll/inner wrappers and the view-all link inside', async () => {
    const wrapper = await openPanel(cards)
    const panel = wrapper.find('aside#notifications.notifications')

    expect(panel.exists()).toBe(true)
    expect(panel.attributes('data-testid')).toBe('notifications-restarters-panel')
    expect(panel.find('.notifications__scroll .notifications__inner .cards').exists()).toBe(true)
    expect(panel.find('.notifications__scroll > a.notifications__older').exists()).toBe(true)
  })

  it('opening the bell shows the latest notification cards and a view-all link', async () => {
    const wrapper = await openPanel(cards)

    expect(wrapper.find('[data-testid="notifications-restarters-panel"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notification-a"]').text()).toContain('Group A')
    expect(wrapper.find('[data-testid="notification-b"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notifications-restarters-empty"]').exists()).toBe(false)
    const link = wrapper.find('[data-testid="notifications-view-all"]')
    expect(link.text()).toBe('View all notifications')
    expect(link.attributes('href')).toBe('/notifications')
  })

  it('shows at most 10 cards', async () => {
    const many = Array.from({ length: 20 }, (_, i) => ({ ...cards[0], id: `n${i}` }))
    const wrapper = await openPanel(many)

    expect(wrapper.findAll('.notification-card')).toHaveLength(10)
  })

  it('shows the up-to-date message when there are no notifications', async () => {
    const wrapper = await openPanel([])

    const empty = wrapper.find('[data-testid="notifications-restarters-empty"]')
    expect(empty.text()).toContain('Thanks! You are up to date')
    expect(empty.text()).toContain("You have nothing to action now. When you do, we'll let you know.")
    expect(wrapper.find('[data-testid="notifications-view-all"]').exists()).toBe(true)
  })

  it('marking a card read calls the API and updates the card and bell count', async () => {
    mockApi.user.markNotificationsRead.mockResolvedValue({ data: { unread: 0 } })
    const wrapper = await openPanel(cards)

    await wrapper.find('[data-testid="notification-mark-a"]').trigger('click')
    await flushPromises()

    expect(mockApi.user.markNotificationsRead).toHaveBeenCalledWith('a')
    expect(wrapper.find('[data-testid="notification-marked-a"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notifications-restarters-count"]').text()).toBe('0')
  })

  it('clicking the bell again closes the panel', async () => {
    const wrapper = await openPanel(cards)

    await wrapper.find('[data-testid="notifications-restarters-badge"]').trigger('click')
    expect(wrapper.find('[data-testid="notifications-restarters-panel"]').exists()).toBe(false)
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
