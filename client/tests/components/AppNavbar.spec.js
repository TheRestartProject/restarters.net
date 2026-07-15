import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AppNavbar from '../../app/components/AppNavbar.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import en from '../../i18n/locales/en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

function mountNavbar() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })

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
