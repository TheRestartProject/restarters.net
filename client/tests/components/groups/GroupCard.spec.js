import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import GroupCard from '../../../app/components/groups/GroupCard.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupCard, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BButton: BButtonStub },
    },
  })
}

describe('components/groups/GroupCard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('links the group name to its view page', () => {
    const wrapper = mountComponent({
      group: { id: 4, name: 'Riverside Fixers', distance: 3.4, location: 'Riverside', image_url: null },
    })

    expect(wrapper.find('[data-testid="group-card-link-4"]').attributes('href')).toBe('/group/view/4')
    expect(wrapper.find('[data-testid="group-card-link-4"]').text()).toBe('Riverside Fixers')
  })

  it('shows location and distance when present', () => {
    const wrapper = mountComponent({
      group: { id: 4, name: 'Riverside Fixers', distance: 3.4, location: 'Riverside', image_url: null },
    })

    expect(wrapper.find('[data-testid="group-card-location-4"]').text()).toBe('Riverside')
    expect(wrapper.find('[data-testid="group-card-distance-4"]').text()).toContain('3.4')
  })

  it('omits distance when null', () => {
    const wrapper = mountComponent({
      group: { id: 4, name: 'Riverside Fixers', distance: null, location: 'Riverside', image_url: null },
    })

    expect(wrapper.find('[data-testid="group-card-distance-4"]').exists()).toBe(false)
  })

  it('shows a join button reflecting the isMember prop', () => {
    const wrapper = mountComponent({
      group: { id: 4, name: 'Riverside Fixers', distance: 3.4, location: 'Riverside', image_url: null },
      isMember: false,
    })

    expect(wrapper.find('[data-testid="group-join-4"]').exists()).toBe(true)
  })
})
