import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AppNavbar from '../../app/components/AppNavbar.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

function mountNavbar() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(AppNavbar, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub },
    },
  })
}

// In the real app, sessionStore.fetch() populates both stores' `user` in
// lockstep (stores/session.js syncs authStore.user - design.md §4.4).
// useAuth()'s hasRole() reads authStore.user.role_name, while the navbar's
// display reads sessionStore.user - so tests set both, exactly as the real
// fetch() would.
function setLoggedInUser(user) {
  useSessionStore().user = user
  useAuthStore().user = user
  useAuthStore().token = 'tok-1'
}

describe('AppNavbar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('navigateTo', vi.fn())
  })

  it('shows login/join links when logged out', () => {
    const wrapper = mountNavbar()

    expect(wrapper.find('[data-testid="nav-login"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nav-join"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nav-user-menu"]').exists()).toBe(false)
  })

  it('shows the user menu (not admin) for a plain Restarter', () => {
    setLoggedInUser({ id: 5, name: 'Jane', role_name: 'Restarter', networks: [] })

    const wrapper = mountNavbar()

    expect(wrapper.find('[data-testid="nav-login"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="nav-user-menu"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nav-admin-menu"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="nav-logout"]').exists()).toBe(true)
  })

  // develop's account toggle (resources/views/layouts/navbar.blade.php:107,109)
  // is avatar-only, with the name carried as alt text ("{name} Profile
  // Picture") rather than a visible <span> - the visible username was a
  // parity gap.
  it('renders the account toggle as avatar-only, with the name only in the img alt text', () => {
    setLoggedInUser({ id: 5, name: 'Jane', role_name: 'Restarter', networks: [] })

    const wrapper = mountNavbar()
    const toggle = wrapper.find('[data-testid="nav-user-menu"]')

    expect(toggle.text()).toBe('')
    expect(toggle.find('img.avatar').attributes('alt')).toBe('Jane Profile Picture')
  })

  // develop's toggle also carries aria-label="Toggle account navigation" on
  // the element itself (navbar.blade.php:107) - since an explicit
  // aria-label wins the accessible-name computation over any descendant
  // img alt, this (not the alt text above) is what actually governs the
  // toggle's accessible name. Asserted separately so the two can't drift
  // apart from each other.
  it('labels the account toggle with an aria-label, matching develop', () => {
    setLoggedInUser({ id: 5, name: 'Jane', role_name: 'Restarter', networks: [] })

    const wrapper = mountNavbar()
    const toggle = wrapper.find('[data-testid="nav-user-menu"]')

    expect(toggle.attributes('aria-label')).toBe('Toggle account navigation')
  })

  it('shows the admin menu for an Administrator', () => {
    setLoggedInUser({ id: 5, name: 'Ada', role_name: 'Administrator', networks: [] })

    const wrapper = mountNavbar()

    expect(wrapper.find('[data-testid="nav-admin-menu"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nav-admin-users"]').exists()).toBe(true)
  })

  it('shows the networks link for a NetworkCoordinator without admin rights', () => {
    setLoggedInUser({
      id: 5,
      name: 'Nadia',
      role_name: 'NetworkCoordinator',
      networks: [{ id: 9, name: 'Test Network' }],
    })

    const wrapper = mountNavbar()

    expect(wrapper.find('[data-testid="nav-admin-menu"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="nav-network-coordinator-menu"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nav-networks"]').attributes('href')).toBe('/networks/9')
  })

  it('logout calls the auth store and redirects to /login', async () => {
    setLoggedInUser({ id: 5, name: 'Jane', role_name: 'Restarter', networks: [] })

    const authStore = useAuthStore()
    authStore.logout = vi.fn().mockResolvedValue()

    const navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    const wrapper = mountNavbar()
    await wrapper.find('[data-testid="nav-logout"]').trigger('click')
    await wrapper.vm.$nextTick()
    await Promise.resolve()

    expect(authStore.logout).toHaveBeenCalledTimes(1)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })
})
