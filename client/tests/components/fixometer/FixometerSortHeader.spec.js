import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import FixometerSortHeader from '../../../app/components/fixometer/FixometerSortHeader.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

function mountHeader(props) {
  return mount(FixometerSortHeader, { props, global: { plugins: [i18n] } })
}

describe('components/fixometer/FixometerSortHeader', () => {
  it('renders plain text (no button) when it has no sortKey', () => {
    const wrapper = mountHeader({ label: 'Assessment' })
    expect(wrapper.find('button').exists()).toBe(false)
    expect(wrapper.text()).toContain('Assessment')
  })

  it('renders a clickable control that emits its sortKey', async () => {
    const wrapper = mountHeader({ label: 'Item', sortKey: 'item_type', activeKey: null, desc: false })
    const button = wrapper.find('[data-testid="device-search-sort-item_type"]')
    expect(button.exists()).toBe(true)
    // aria-sort deliberately absent here: ARIA only honours it on the <th>,
    // so DevicesSearchTable sets it there. See composables/useSortAria.js.
    expect(button.attributes('aria-sort')).toBeUndefined()
    expect(button.text()).toContain(clientEn.client.common.sort_ascending)

    await button.trigger('click')
    expect(wrapper.emitted('sort')).toEqual([['item_type']])
  })

  it('highlights the active direction, and offers the opposite one', () => {
    const asc = mountHeader({ label: 'Item', sortKey: 'item_type', activeKey: 'item_type', desc: false })
    expect(asc.find('.sort-header__up').classes()).toContain('sort-header__on')
    expect(asc.find('.sort-header__down').classes()).not.toContain('sort-header__on')
    // Already ascending, so a click sorts the other way - the hint announces
    // what the control WILL do, not the state it is already in.
    expect(asc.text()).toContain(clientEn.client.common.sort_descending)

    const desc = mountHeader({ label: 'Item', sortKey: 'item_type', activeKey: 'item_type', desc: true })
    expect(desc.find('.sort-header__down').classes()).toContain('sort-header__on')
    expect(desc.text()).toContain(clientEn.client.common.sort_ascending)
  })

  it('shows no active arrow when a different column is the active sort', () => {
    const wrapper = mountHeader({ label: 'Item', sortKey: 'item_type', activeKey: 'brand', desc: false })
    expect(wrapper.find('.sort-header__on').exists()).toBe(false)
    expect(wrapper.text()).toContain(clientEn.client.common.sort_ascending)
  })
})
