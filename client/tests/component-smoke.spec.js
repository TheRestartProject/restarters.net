import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'
import { describe, expect, it } from 'vitest'

// A trivial stand-in component using a bootstrap-vue-next component name,
// mounted with global.stubs the way real components will be tested
// (design.md §8 / task conventions) without pulling in the full Bootstrap
// runtime.
const Greeting = defineComponent({
  name: 'Greeting',
  props: { label: { type: String, required: true } },
  setup(props) {
    return () =>
      h('div', { 'data-testid': 'greeting' }, [
        h('BButton', { 'data-testid': 'greeting-button' }, props.label),
      ])
  },
})

describe('component smoke test', () => {
  it('mounts with a bootstrap-vue-next component stubbed', () => {
    const wrapper = mount(Greeting, {
      props: { label: 'Hello Restarters' },
      global: {
        stubs: { BButton: true },
      },
    })

    expect(wrapper.get('[data-testid="greeting"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Hello Restarters')
  })
})
