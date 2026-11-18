import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import DashboardTalkTopic from '../../../app/components/dashboard/DashboardTalkTopic.vue'
import { useSessionStore } from '../../../app/stores/session.js'
import { stubSsoBridge, talkSso } from '../../helpers/ssoBridge.js'

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
  beforeEach(() => {
    setActivePinia(createPinia())
    useSessionStore().config = { discourse_url: 'https://talk.restarters.net' }
  })

  it('links the title to discourseBaseUrl/t/{slug}/{id}', () => {
    const wrapper = mountRow()
    const link = wrapper.find('[data-testid="talk-topic-link-7"]')
    expect(link.text()).toBe('Fixing kettles')
    expect(link.attributes('href')).toBe('https://talk.restarters.net/t/fixing-kettles/7')
  })

  // #966: a plain link left someone not yet logged in to Talk at "Access
  // Denied" for topics that need an account.
  it('clicking the title goes through Talk\'s SSO, so Talk logs them in first', async () => {
    const bridge = stubSsoBridge()
    try {
      await mountRow().find('[data-testid="talk-topic-link-7"]').trigger('click')

      expect(await bridge.redirect()).toBe(
        talkSso('https://talk.restarters.net', 'https://talk.restarters.net/t/fixing-kettles/7')
      )
    } finally {
      bridge.restore()
    }
  })

  it('clicking the category badge goes through Talk\'s SSO too', async () => {
    const bridge = stubSsoBridge()
    try {
      await mountRow().find('.talk-topic__badge').trigger('click')

      expect(await bridge.redirect()).toBe(talkSso('https://talk.restarters.net', 'https://talk.restarters.net/c/kettles'))
    } finally {
      bridge.restore()
    }
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
