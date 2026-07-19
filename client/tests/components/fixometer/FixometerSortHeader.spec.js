import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import FixometerSortHeader from '../../../app/components/fixometer/FixometerSortHeader.vue'

describe('components/fixometer/FixometerSortHeader', () => {
  it('renders plain text (no button) when it has no sortKey', () => {
    const wrapper = mount(FixometerSortHeader, { props: { label: 'Assessment' } })
    expect(wrapper.find('button').exists()).toBe(false)
    expect(wrapper.text()).toContain('Assessment')
  })

  it('renders a clickable control that emits its sortKey', async () => {
    const wrapper = mount(FixometerSortHeader, {
      props: { label: 'Item', sortKey: 'item_type', activeKey: null, desc: false },
    })
    const button = wrapper.find('[data-testid="device-search-sort-item_type"]')
    expect(button.exists()).toBe(true)
    expect(button.attributes('aria-sort')).toBe('none')

    await button.trigger('click')
    expect(wrapper.emitted('sort')).toEqual([['item_type']])
  })

  it('reflects the active ascending/descending state via aria-sort and the highlighted arrow', () => {
    const asc = mount(FixometerSortHeader, {
      props: { label: 'Item', sortKey: 'item_type', activeKey: 'item_type', desc: false },
    })
    expect(asc.find('button').attributes('aria-sort')).toBe('ascending')
    expect(asc.find('.sort-header__up').classes()).toContain('sort-header__on')
    expect(asc.find('.sort-header__down').classes()).not.toContain('sort-header__on')

    const desc = mount(FixometerSortHeader, {
      props: { label: 'Item', sortKey: 'item_type', activeKey: 'item_type', desc: true },
    })
    expect(desc.find('button').attributes('aria-sort')).toBe('descending')
    expect(desc.find('.sort-header__down').classes()).toContain('sort-header__on')
  })

  it('shows no active arrow when a different column is the active sort', () => {
    const wrapper = mount(FixometerSortHeader, {
      props: { label: 'Item', sortKey: 'item_type', activeKey: 'brand', desc: false },
    })
    expect(wrapper.find('button').attributes('aria-sort')).toBe('none')
    expect(wrapper.find('.sort-header__on').exists()).toBe(false)
  })
})
