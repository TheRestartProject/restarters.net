import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DashboardTalkTopic from '../../../app/components/dashboard/DashboardTalkTopic.vue'

function topic(overrides = {}) {
  return {
    id: 7,
    title: 'Fixing kettles',
    slug: 'fixing-kettles',
    posts_count: 5,
    created_at: '2026-01-01T00:00:00Z',
    category: { color: 'ff0000', name: 'Kettles', topic_url: '/c/kettles' },
    ...overrides,
  }
}

function mountRow(props = {}) {
  return mount(DashboardTalkTopic, {
    props: { topic: topic(), discourseBaseUrl: 'https://talk.restarters.net', ...props },
    // <tr> must mount inside a table for valid DOM; wrap it.
    attachTo: document.createElement('tbody'),
  })
}

describe('components/dashboard/DashboardTalkTopic', () => {
  it('links the title to discourseBaseUrl/t/{slug}', () => {
    const wrapper = mountRow()
    const link = wrapper.find('[data-testid="talk-topic-link-7"]')
    expect(link.text()).toBe('Fixing kettles')
    expect(link.attributes('href')).toBe('https://talk.restarters.net/t/fixing-kettles')
  })

  it('renders the comment count', () => {
    expect(mountRow().text()).toContain('5')
  })

  it('renders the category badge (colour dot + name) linking to the category', () => {
    const wrapper = mountRow()
    const badge = wrapper.find('.talk-topic__badge')
    expect(badge.attributes('href')).toBe('https://talk.restarters.net/c/kettles')
    expect(wrapper.find('.talk-topic__badge-name').text()).toBe('Kettles')
    expect(wrapper.find('.talk-topic__badge-dot').attributes('style')).toContain('#ff0000')
  })

  it('omits the category badge when the topic has no category', () => {
    const wrapper = mountRow({ topic: topic({ category: null }) })
    expect(wrapper.find('.talk-topic__badge').exists()).toBe(false)
    // The title link still renders.
    expect(wrapper.find('[data-testid="talk-topic-link-7"]').exists()).toBe(true)
  })
})
