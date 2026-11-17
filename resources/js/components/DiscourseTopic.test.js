import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount } from '@vue/test-utils'
import DiscourseTopic from './DiscourseTopic.vue'

const base = 'https://talk.example.org'

function makeWrapper() {
  // DiscourseTopic renders a table row, so it needs a table around it.
  return mount({
    components: { DiscourseTopic },
    data: () => ({
      base,
      topic: {
        id: 44,
        slug: 'who-is-everyone-meet-and-greet',
        title: 'Who is everyone? Meet and greet',
        posts_count: 3,
        created_at: '2026-01-01T00:00:00Z',
        category: { name: 'Community', color: 'ff0000', topic_url: '/t/about-the-community-category/5' },
      },
    }),
    template: '<table><tbody><DiscourseTopic :topic="topic" :discourse-base-url="base" /></tbody></table>',
  })
}

test('links to the topic through Discourse SSO, so Talk logs you in first', () => {
  const links = makeWrapper().findAll('a')

  expect(links.at(0).text()).toBe('Who is everyone? Meet and greet')
  expect(links.at(0).attributes('href')).toBe(
    base + '/session/sso?return_path=' + encodeURIComponent(base + '/t/who-is-everyone-meet-and-greet/44')
  )
})

test('links to the category through Discourse SSO too', () => {
  const links = makeWrapper().findAll('a')

  expect(links.at(1).attributes('href')).toBe(
    base + '/session/sso?return_path=' + encodeURIComponent(base + '/t/about-the-community-category/5')
  )
})
