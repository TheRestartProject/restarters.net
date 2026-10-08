import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DashboardPage from '../../app/pages/dashboard.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import { useDashboardStore } from '../../app/stores/dashboard.js'
import { useEventsStore } from '../../app/stores/events.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BFormSelectStub = { props: ['modelValue', 'options'], template: '<select />' }
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue" data-testid="modal-stub"><slot /></div>',
}

const emptyData = { your_groups: [], nearby_groups: [], new_nearby_groups: [], upcoming_events: [] }

function mountDashboard() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardPage, {
    global: {
      plugins: [i18n],
      stubs: {
        NuxtLink: NuxtLinkStub,
        BAlert: BAlertStub,
        BButton: BButtonStub,
        BBadge: BBadgeStub,
        BModal: BModalStub,
        BFormSelect: BFormSelectStub,
      },
    },
  })
}

describe('pages/dashboard', () => {
  let dashboardStore

  beforeEach(() => {
    setActivePinia(createPinia())

    dashboardStore = useDashboardStore()
    dashboardStore.fetch = vi.fn().mockResolvedValue(emptyData)

    // dashboard.vue also fetches the user's events (for the Add Data picker).
    useEventsStore().fetchMyEvents = vi.fn().mockResolvedValue([])

    const authStore = useAuthStore()
    authStore.user = { id: 1, name: 'Jane' }
  })

  it('calls dashboardStore.fetch() on mount', () => {
    mountDashboard()
    expect(dashboardStore.fetch).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton and no content/error while loading', () => {
    dashboardStore.loading = true

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="dashboard-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="dashboard-content"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="dashboard-error"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetch again', async () => {
    dashboardStore.error = { status: 500 }

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="dashboard-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="dashboard-content"]').exists()).toBe(false)

    await wrapper.find('[data-testid="dashboard-retry"]').trigger('click')
    expect(dashboardStore.fetch).toHaveBeenCalledTimes(2)
  })

  it('renders the your-groups empty state when the user has no groups', () => {
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="dashboard-content"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="your-groups-empty"]').exists()).toBe(true)
  })

  it('hides the nearby-groups list and shows a set-location CTA when the user has no location', () => {
    dashboardStore.data = { ...emptyData, has_location: false }

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="nearby-groups-no-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nearby-groups-no-location"]').html()).toContain('/profile/edit')
    expect(wrapper.find('[data-testid="nearby-groups-list"]').exists()).toBe(false)
  })

  it('shows the upcoming-events empty state when the user has groups but no upcoming events', () => {
    dashboardStore.data = {
      ...emptyData,
      your_groups: [{ id: 1, name: 'A Group', role: 4, archived: false, image_url: null }],
    }

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="upcoming-events-empty"]').exists()).toBe(true)
  })

  it('does not render the upcoming-events section at all when the user has no groups', () => {
    // Legacy DashboardYourGroups.vue only ever shows the events list
    // alongside the user's own groups (v-else branch) - a user with no
    // groups sees the DashboardNoGroups fallback instead, with no events
    // section anywhere on the page.
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="dashboard-upcoming-events"]').exists()).toBe(false)
  })

  it('renders the dashboard sections in the legacy order: getting started, your groups, add data, what\'s happening', () => {
    dashboardStore.data = {
      ...emptyData,
      your_groups: [{ id: 1, name: 'A Group', role: 4, archived: false, image_url: null }],
    }
    useEventsStore().myEvents.data = [
      { id: 1, title: 'Event', start: '2026-08-01T10:00:00Z', group: { id: 1, name: 'A Group' } },
    ]

    const wrapper = mountDashboard()

    const testids = ['dashboard-getting-started', 'dashboard-yourgroups-panel', 'dashboard-add-data', 'dashboard-whats-happening']
    const positions = testids.map((id) => wrapper.html().indexOf(`data-testid="${id}"`))

    expect(positions.every((p) => p !== -1)).toBe(true)
    expect(positions).toEqual([...positions].sort((a, b) => a - b))
  })

  it('nests the nearby-groups fallback inside the single Your Groups panel, not as its own panel', () => {
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    const panel = wrapper.find('[data-testid="dashboard-yourgroups-panel"]')
    expect(panel.find('[data-testid="dashboard-nearby-groups"]').exists()).toBe(true)

    // Only the one panel wraps the your-groups content - no separate
    // always-visible "Groups near you" panel alongside it.
    expect(wrapper.find('[data-testid="dashboard-content"]').findAll('[data-testid="dashboard-nearby-groups"]').length).toBe(1)
  })

  it('shows the "What\'s happening" section with a see-all link', () => {
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="dashboard-whats-happening"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="whats-happening-see-all"]').exists()).toBe(true)
  })

  it('opens the onboarding modal when the session flags it, and dismiss clears it', async () => {
    const sessionStore = useSessionStore()
    sessionStore.flags = { onboarding: true }
    sessionStore.dismissOnboarding = vi.fn().mockResolvedValue()
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="onboarding-modal"]').exists()).toBe(true)

    // Legacy only reveals the X close button once the carousel reaches its
    // last slide (see DashboardOnboardingModal.vue) - advance through the
    // other slides first, matching that behaviour, rather than expecting it
    // to be present from the first slide.
    expect(wrapper.find('[data-testid="onboarding-close"]').exists()).toBe(false)
    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')

    await wrapper.find('[data-testid="onboarding-close"]').trigger('click')

    expect(sessionStore.dismissOnboarding).toHaveBeenCalledTimes(1)
  })

  it('does not render the onboarding modal when the session does not flag it', () => {
    const sessionStore = useSessionStore()
    sessionStore.flags = { onboarding: false }
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="onboarding-modal"]').exists()).toBe(false)
  })
})
