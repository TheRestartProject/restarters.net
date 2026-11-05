import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NotificationsPage from '../../app/pages/notifications.vue'
import { useNotificationsStore } from '../../app/stores/notifications.js'
import { useProfileStore } from '../../app/stores/profile.js'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }
const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BPaginationStub = {
  props: ['modelValue'],
  template: '<div data-testid="stub-pagination" @click="$emit(\'update:modelValue\', modelValue + 1)"><slot /></div>',
}

// lang/en/notifications.php gained `marked_as_read` alongside this Nuxt work
// (RES gap-closure pass, gap 6) but client/i18n/locales/en.json is a
// generated, checked-in artifact this change intentionally leaves untouched
// (php artisan translations:export-client) - overlay the key here so the
// spec doesn't depend on regenerating it. Same convention as
// PublicProfileView.spec.js's users.* overlay.
const messages = {
  en: {
    ...en,
    ...clientEn,
    notifications: {
      ...en.notifications,
      marked_as_read: 'Marked as read',
    },
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })
  return mount(NotificationsPage, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub, NuxtLink: NuxtLinkStub, BPagination: BPaginationStub },
    },
  })
}

describe('pages/notifications', () => {
  let store
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNotificationsStore()
    store.fetchList = vi.fn().mockResolvedValue()
    store.markRead = vi.fn().mockResolvedValue({ unread: 0 })
    profileStore = useProfileStore()
    profileStore.fetchRepairDirectoryOptions = vi.fn().mockResolvedValue()
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

    const time = wrapper.find('[data-testid="notification-n1"] time')
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

  // Gap 1: legacy reuses the full profile-edit chrome (heading + View
  // Profile button + the list-group tab sidebar) around the notification
  // list, rather than standing alone.
  describe('profile-edit chrome (gap 1)', () => {
    it('shows the "Profile & Preferences" heading, a View Profile button, and the tab sidebar with Notifications active', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.text()).toContain('Profile & Preferences')
      expect(wrapper.find('[data-testid="notifications-view-profile"]').attributes('href')).toBe('/profile')

      const sidebar = wrapper.find('[data-testid="notifications-sidebar"]')
      expect(sidebar.find('[data-testid="notifications-sidebar-profile"]').attributes('href')).toBe('/profile/edit')
      expect(sidebar.find('[data-testid="notifications-sidebar-account"]').attributes('href')).toBe('/profile/edit')
      expect(sidebar.find('[data-testid="notifications-sidebar-email"]').attributes('href')).toBe('/profile/edit')
      expect(sidebar.find('[data-testid="notifications-sidebar-calendars"]').attributes('href')).toBe('/profile/edit')
      expect(sidebar.find('[data-testid="notifications-sidebar-active"]').text()).toBe('Notifications')
    })

    it('hides the Repair Directory sidebar link until the store resolves it visible', async () => {
      const wrapper = mountPage()
      await flushPromises()
      expect(wrapper.find('[data-testid="notifications-sidebar-repair-directory"]').exists()).toBe(false)

      profileStore.repairDirectory.data = { current: 0, options: [{ value: 1, disabled: false }] }
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="notifications-sidebar-repair-directory"]').attributes('href')).toBe('/profile/edit')
    })

    it('fetches repair-directory-options for the current user on mount', async () => {
      const authStore = useAuthStore()
      authStore.user = { id: 7, role_name: 'Restarter' }

      mountPage()
      await flushPromises()

      expect(profileStore.fetchRepairDirectoryOptions).toHaveBeenCalledWith(7)
    })
  })

  // Gap 6: unread cards get the orange highlight (not read ones) and a
  // category icon instead of a border colour; the mark-as-read control
  // toggles to a "Marked as read" confirmation rather than disappearing.
  describe('card highlighting/type-icon (gap 6)', () => {
    it('highlights unread cards, not read ones', async () => {
      store.list.items = [
        { id: 'n1', type: 'JoinGroup', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
        { id: 'n2', type: 'JoinGroup', title: 'T', name: null, url: null, read: true, created_at: '2026-07-17T10:00:00Z' },
      ]
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="notification-n1"]').classes()).toContain('notification-card--unread')
      expect(wrapper.find('[data-testid="notification-n2"]').classes()).not.toContain('notification-card--unread')
    })

    it('gives a mapped category its icon class, and an unmapped one none', async () => {
      store.list.items = [
        { id: 'n1', type: 'JoinGroup', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
        { id: 'n2', type: 'SomethingUnmapped', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' },
      ]
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="notification-n1"]').classes()).toContain('notification-card--groups')
      expect(wrapper.find('[data-testid="notification-n2"]').classes().some((c) => c.startsWith('notification-card--') && c !== 'notification-card--unread')).toBe(false)
    })

    it('shows a "Marked as read" confirmation in place of the button once read, rather than removing the control', async () => {
      store.list.items = [
        { id: 'n1', type: 'X', title: 'T', name: null, url: null, read: true, created_at: '2026-07-17T10:00:00Z' },
      ]
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="notification-mark-n1"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="notification-marked-n1"]').text()).toContain('Marked as read')
    })
  })

  // Gap 13: a numbered paginator (BPagination), not a bare prev/next pair.
  it('renders a BPagination widget instead of prev/next buttons when there is more than one page', async () => {
    store.list.items = [{ id: 'n1', type: 'X', title: 'T', name: null, url: null, read: false, created_at: '2026-07-17T10:00:00Z' }]
    store.list.page = 1
    store.list.lastPage = 3
    store.list.total = 45
    const wrapper = mountPage()
    await flushPromises()

    const pagination = wrapper.find('[data-testid="notifications-pagination"]')
    expect(pagination.exists()).toBe(true)

    await pagination.trigger('click')
    expect(store.fetchList).toHaveBeenCalledWith(2)
  })
})
