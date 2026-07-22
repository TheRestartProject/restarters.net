import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupInfoModal from '../../../app/components/groups/GroupInfoModal.vue'

// Port of develop's GroupInfoModal.vue (PR 887 / RES-1995 map of groups): a
// marker click opens a modal with the group's name/location, its next event,
// and a "Go to group" action. develop reaches it from a Vue-rendered marker;
// 898's markers are imperative Leaflet layers, so the map emits `select` and
// the page renders this - these tests cover the modal itself.
const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

// Renders the slots inline so title/body/footer are assertable without driving
// bootstrap-vue-next's real modal.
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide', 'update:modelValue'],
  template: `<div v-if="modelValue" data-testid="bmodal">
    <header><slot name="title" /></header>
    <main><slot /></main>
    <footer><slot name="footer" /></footer>
  </div>`,
}

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  messages: {
    en: {
      groups: {
        goto_group: 'Go to group',
        next_event: 'Next event',
        upcoming_none_planned: 'None planned',
      },
      partials: { close: 'Close' },
    },
  },
})

function mountModal(group) {
  return mount(GroupInfoModal, {
    props: { group },
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BModal: BModalStub, BButton: NuxtLinkStub, BImg: true } },
  })
}

const GROUP = {
  id: 42,
  name: 'Hackney Fixers',
  location: 'London',
  image: 'abc.jpg',
  nextEvent: { start: '2026-11-16T13:00:00Z', title: 'Passing Clouds' },
}

describe('components/groups/GroupInfoModal', () => {
  it('renders nothing when no group is selected', () => {
    const wrapper = mountModal(null)
    expect(wrapper.find('[data-testid="bmodal"]').exists()).toBe(false)
  })

  it('shows the group name and location, linked to the group view', () => {
    const wrapper = mountModal(GROUP)

    expect(wrapper.text()).toContain('Hackney Fixers')
    expect(wrapper.text()).toContain('London')
    // The header links to the group, same destination as Go to group.
    const headerLink = wrapper.find('header a')
    expect(headerLink.attributes('href')).toBe('/group/view/42')
  })

  it('shows the next event date and title', () => {
    const wrapper = mountModal(GROUP)

    expect(wrapper.text()).toContain('Next event')
    expect(wrapper.text()).toContain('Passing Clouds')
    // Formatted like GroupsTable's dateLabel (locale day/short-month/year).
    expect(wrapper.text()).toMatch(/Nov 16, 2026/)
  })

  it('shows "None planned" when the group has no next event', () => {
    const wrapper = mountModal({ ...GROUP, nextEvent: null })

    expect(wrapper.text()).toContain('None planned')
    expect(wrapper.text()).not.toContain('Passing Clouds')
  })

  it('has a Go to group action linking to the group view', () => {
    const wrapper = mountModal(GROUP)

    const goto = wrapper.find('[data-testid="group-info-goto"]')
    expect(goto.exists()).toBe(true)
    expect(goto.attributes('href')).toBe('/group/view/42')
    expect(goto.text()).toContain('Go to group')
  })

  it('emits close from the close button and when the modal hides', async () => {
    const wrapper = mountModal(GROUP)

    await wrapper.find('[data-testid="group-info-close"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()

    await wrapper.findComponent(BModalStub).vm.$emit('hide')
    expect(wrapper.emitted('close').length).toBe(2)
  })
})
