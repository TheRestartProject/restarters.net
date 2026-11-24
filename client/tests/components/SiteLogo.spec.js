import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import SiteLogo from '../../app/components/SiteLogo.vue'
import { useSessionStore } from '../../app/stores/session.js'

// Live's includes/logo.blade.php: a network's own site (repairtogether.restarters.net
// etc.) shows that network's logo in place of the Restarters wordmark.
describe('components/SiteLogo', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  function mountLogo() {
    return mount(SiteLogo, { global: { stubs: { IconLogo: { template: '<svg data-testid="wordmark" />' } } } })
  }

  it("shows the network's logo on a network's site", () => {
    useSessionStore().site = {
      shortname: 'repairtogether',
      name: 'Repair Together',
      logo: 'https://restarters.net/images/logos/repairtogether.png',
    }

    const wrapper = mountLogo()
    const img = wrapper.get('[data-testid="site-logo-network"]')

    expect(img.attributes('src')).toBe('https://restarters.net/images/logos/repairtogether.png')
    expect(img.attributes('height')).toBe('49')
    expect(img.attributes('alt')).toBe('Repair Together')
    expect(wrapper.find('[data-testid="wordmark"]').exists()).toBe(false)
  })

  it('shows the Restarters wordmark on the Restarters site', () => {
    useSessionStore().site = { shortname: 'restarters', name: 'Restarters', logo: null }

    const wrapper = mountLogo()

    expect(wrapper.find('[data-testid="wordmark"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="site-logo-network"]').exists()).toBe(false)
  })

  it('shows the Restarters wordmark before the site is known', () => {
    const wrapper = mountLogo()

    expect(wrapper.find('[data-testid="wordmark"]').exists()).toBe(true)
  })
})
