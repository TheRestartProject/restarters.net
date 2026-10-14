import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import GroupCollapsibleSection from '../../../app/components/groups/GroupCollapsibleSection.vue'

const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function mountComponent(props = {}, slots = {}) {
  return mount(GroupCollapsibleSection, {
    props,
    slots: {
      title: '<span data-testid="title-slot">Title</span>',
      default: '<p data-testid="content-slot">Content</p>',
      ...slots,
    },
    global: { stubs: { BBadge: BBadgeStub } },
  })
}

describe('components/groups/GroupCollapsibleSection', () => {
  it('renders title and content slots', () => {
    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="title-slot"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="content-slot"]').exists()).toBe(true)
  })

  it('keeps content in the DOM even when collapsed by default (mobile-only CSS hide)', () => {
    const wrapper = mountComponent({ collapsed: true })
    // The content stays in the DOM (class-toggled d-none, not v-if removed) so
    // desktop's d-md-block always shows it - only mobile viewports hide it.
    expect(wrapper.find('[data-testid="content-slot"]').exists()).toBe(true)
  })

  it('toggles expanded state on title click', async () => {
    const wrapper = mountComponent({ collapsed: true })
    const contentWrapper = wrapper.find('[data-testid="content-slot"]').element.parentElement

    expect(contentWrapper.classList.contains('d-none')).toBe(true)

    await wrapper.find('.collapsible-title').trigger('click')
    expect(contentWrapper.classList.contains('d-none')).toBe(false)
  })

  it('shows a count badge when count is set', () => {
    const wrapper = mountComponent({ count: 4 })
    expect(wrapper.text()).toContain('4')
  })
})
