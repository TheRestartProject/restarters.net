import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import NetworkNearbyGroups from '../../../app/components/networks/NetworkNearbyGroups.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

// The networks.nearby strings come from the regenerated client-en.json / en.json.
function mountComponent(props) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(NetworkNearbyGroups, {
    props,
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('NetworkNearbyGroups', () => {
  it('lists each nearby group with its link, location and distance', () => {
    const wrapper = mountComponent({
      nearby: {
        from: 'Alpha, Beta',
        groups: [
          { id: 5, name: 'Close By', location: 'Townsville', distance: 1.2 },
          { id: 6, name: 'Further', location: null, distance: 12.5 },
        ],
      },
    })

    expect(wrapper.text()).toContain('Groups near you')
    expect(wrapper.text()).toContain('Alpha, Beta')

    const first = wrapper.get('[data-testid="network-nearby-group-5"]')
    expect(first.text()).toContain('Close By')
    expect(first.text()).toContain('Townsville')
    expect(first.text()).toContain('1.2 km')
    expect(first.get('a').attributes('href')).toBe('/group/view/5')

    expect(wrapper.get('[data-testid="network-nearby-group-6"]').text()).toContain('12.5 km')
  })

  it('renders nothing when the viewer has no located group in the network', () => {
    expect(mountComponent({ nearby: null }).find('[data-testid="network-nearby-groups"]').exists()).toBe(false)
  })

  it('renders nothing when there are no other groups nearby', () => {
    const wrapper = mountComponent({ nearby: { from: 'Alpha', groups: [] } })
    expect(wrapper.find('[data-testid="network-nearby-groups"]').exists()).toBe(false)
  })
})
