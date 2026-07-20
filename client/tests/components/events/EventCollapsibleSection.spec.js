import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import EventCollapsibleSection from '../../../app/components/events/EventCollapsibleSection.vue'
import { BBadgeStub } from '../../helpers/stubs.js'

function mountComponent(props = {}, slots = {}) {
  return mount(EventCollapsibleSection, {
    props,
    slots: { title: '<h2>Section title</h2>', default: '<p data-testid="section-body">Body</p>', ...slots },
    global: { stubs: { BBadge: BBadgeStub } },
  })
}

// Gap 7: mobile-collapsed-by-default section with a count badge next to the
// heading, matching develop's CollapsibleSection.vue (via
// components/groups/GroupCollapsibleSection.vue's trimmed port).
describe('components/events/EventCollapsibleSection', () => {
  it('renders the title slot and default slot content', () => {
    const wrapper = mountComponent()
    expect(wrapper.text()).toContain('Section title')
    expect(wrapper.find('[data-testid="section-body"]').exists()).toBe(true)
  })

  it('shows a count badge only when count is set', () => {
    expect(mountComponent({ count: 3 }).find('[data-testid="event-collapsible-count-badge"]').text()).toBe('3')
    expect(mountComponent({ count: null }).find('[data-testid="event-collapsible-count-badge"]').exists()).toBe(false)
  })

  it('starts collapsed by default, and toggles open on click of the title', async () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="event-collapsible-body"]').classes()).toContain('d-none')

    await wrapper.find('[data-testid="event-collapsible-title"]').trigger('click')
    expect(wrapper.find('[data-testid="event-collapsible-body"]').classes()).not.toContain('d-none')
  })

  it('starts expanded when collapsed is false', () => {
    const wrapper = mountComponent({ collapsed: false })
    expect(wrapper.find('[data-testid="event-collapsible-body"]').classes()).not.toContain('d-none')
  })
})
