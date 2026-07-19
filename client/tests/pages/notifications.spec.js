import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NotificationsPage from '../../app/pages/notifications.vue'
import { useNotificationsStore } from '../../app/stores/notifications.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  return mount(NotificationsPage, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub },
    },
  })
}

describe('pages/notifications', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNotificationsStore()
    store.fetchList = vi.fn().mockResolvedValue()
    store.markRead = vi.fn().mockResolvedValue({ unread: 0 })
  })

  it('fetches the notifications on mount', async () => {
    mountPage()
    await flushPromises()
    expect(store.fetchList).toHaveBeenCalledWith(1)
  })

  it('shows the empty state when there are no notifications', async () => {
    store.list.items = []
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.find('[data-testid="notifications-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notifications-list"]').exists()).toBe(false)
  })

  it('renders a notification with its title/link and mark-as-read control', async () => {
    store.list.items = [
      { id: 'n1', type: 'NewGroupWithinRadius', title: 'New group', name: 'Repair Cafe', url: '/group/view/5', read: false, created_at: '2026-07-17T10:00:00+00:00' },
    ]
    store.list.unread = 1
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="notification-n1"]').text()).toContain('New group')
    expect(wrapper.find('[data-testid="notification-n1"] a').attributes('href')).toBe('/group/view/5')
    expect(wrapper.find('[data-testid="notification-mark-n1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="notifications-mark-all"]').exists()).toBe(true)
  })

  it('renders the created_at timestamp as relative time, with the absolute datetime in a title', async () => {
    const now = new Date('2026-07-17T12:00:00Z')
    vi.useFakeTimers()
    vi.setSystemTime(now)

    store.list.items = [
      { id: 'n1', type: 'JoinGroup', title: null, name: 'Repair Cafe', url: '/group/view/5', read: false, created_at: '2026-07-14T12:00:00Z' },
    ]
    const wrapper = mountPage()
    await flushPromises()

    const time = wrapper.find('[data-testid="notification-n1"] small')
    expect(time.text()).toBe('3 days ago')
    expect(time.attributes('title')).toBe(
      new Intl.DateTimeFormat('en', { dateStyle: 'full', timeStyle: 'short' }).format(new Date('2026-07-14T12:00:00Z'))
    )

    vi.useRealTimers()
  })

  it('buckets the notification type into a category class, mirroring Fixometer::notificationClasses', async () => {
    store.list.items = [
      { id: 'n1', type: 'AdminNewUser', title: null, name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
      { id: 'n2', type: 'JoinEvent', title: null, name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
      { id: 'n3', type: 'JoinGroup', title: null, name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
      { id: 'n4', type: 'AdminAbnormalDevices', title: null, name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
      { id: 'n5', type: 'SomethingUnmapped', title: null, name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
    ]
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="notification-n1"]').attributes('data-notification-category')).toBe('user')
    expect(wrapper.find('[data-testid="notification-n2"]').attributes('data-notification-category')).toBe('event')
    expect(wrapper.find('[data-testid="notification-n3"]').attributes('data-notification-category')).toBe('group')
    expect(wrapper.find('[data-testid="notification-n4"]').attributes('data-notification-category')).toBe('device')
    expect(wrapper.find('[data-testid="notification-n5"]').attributes('data-notification-category')).toBe('other')
  })

  it('marks a single notification read', async () => {
    store.list.items = [
      { id: 'n1', type: 'X', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00+00:00' },
    ]
    store.list.unread = 1
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="notification-mark-n1"]').trigger('click')
    expect(store.markRead).toHaveBeenCalledWith('n1')
  })

  it('marks all notifications read', async () => {
    store.list.items = [{ id: 'n1', type: 'X', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00+00:00' }]
    store.list.unread = 2
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="notifications-mark-all"]').trigger('click')
    expect(store.markRead).toHaveBeenCalledWith(null)
  })
})
