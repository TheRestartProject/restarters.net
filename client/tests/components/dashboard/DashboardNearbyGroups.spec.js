import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardNearbyGroups from '../../../app/components/dashboard/DashboardNearbyGroups.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const BButtonStub = {
  props: ['to'],
  template: '<a :href="to" v-bind="$attrs"><slot /></a>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardNearbyGroups, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BButton: BButtonStub },
    },
  })
}

describe('components/dashboard/DashboardNearbyGroups', () => {
  it('shows a "set your location" prompt with no join CTAs when the user has no location', () => {
    const wrapper = mountComponent({ nearbyGroups: [], hasLocation: false })

    expect(wrapper.find('[data-testid="nearby-groups-no-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nearby-groups-no-location"]').html()).toContain('/profile/edit')
    expect(wrapper.find('[data-testid="nearby-groups-list"]').exists()).toBe(false)
  })

  it('shows an empty state when the user has a location but no nearby groups', () => {
    const wrapper = mountComponent({ nearbyGroups: [], hasLocation: true })

    expect(wrapper.find('[data-testid="nearby-groups-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="nearby-groups-no-location"]').exists()).toBe(false)
  })

  it('renders nearby groups with distance and a join CTA linking to the group page', () => {
    const wrapper = mountComponent({
      hasLocation: true,
      nearbyGroups: [{ id: 5, name: 'Nearby Fixers', distance: 3.2, location: 'Bristol', image_url: null }],
    })

    expect(wrapper.find('[data-testid="nearby-group-link-5"]').attributes('href')).toBe('/group/view/5')
    expect(wrapper.find('[data-testid="nearby-group-distance-5"]').text()).toContain('3.2')
    expect(wrapper.find('[data-testid="nearby-group-location-5"]').text()).toBe('Bristol')
    expect(wrapper.find('[data-testid="nearby-group-join-5"]').attributes('href')).toBe('/group/view/5')
  })

  it('has no heading or panel of its own - it nests inside the "Your Groups" panel as its empty state', () => {
    const wrapper = mountComponent({ nearbyGroups: [], hasLocation: true })

    expect(wrapper.find('h2').exists()).toBe(false)
  })

  it('does not render the photo when the user has no location (matches the live legacy dashboard)', () => {
    const wrapper = mountComponent({ nearbyGroups: [], hasLocation: false })

    expect(wrapper.find('.nearby-layout__pic').exists()).toBe(false)
  })

  it('always shows the "groups all over the world" copy when the user has a location, even alongside a nearby-groups list', () => {
    const wrapper = mountComponent({
      hasLocation: true,
      nearbyGroups: [{ id: 5, name: 'Nearby Fixers', distance: 3.2, location: 'Bristol', image_url: null }],
    })

    expect(wrapper.find('[data-testid="nearby-groups-empty"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('groups all over the world')
  })
})
