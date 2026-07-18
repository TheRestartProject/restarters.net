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

  it('shows the upcoming-events empty state when there are no upcoming events', () => {
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="upcoming-events-empty"]').exists()).toBe(true)
  })

  it('opens the onboarding modal when the session flags it, and dismiss clears it', async () => {
    const sessionStore = useSessionStore()
    sessionStore.flags = { onboarding: true }
    sessionStore.dismissOnboarding = vi.fn().mockResolvedValue()
    dashboardStore.data = emptyData

    const wrapper = mountDashboard()

    expect(wrapper.find('[data-testid="onboarding-modal"]').exists()).toBe(true)

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
