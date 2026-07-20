import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupShareStatsModal from '../../../app/components/groups/GroupShareStatsModal.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { BModalStub, GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupShareStatsModal, {
    props: { groupId: 5, groupName: 'Fixers United', ...props },
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

describe('components/groups/GroupShareStatsModal', () => {
  it('renders nothing when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-testid="group-share-stats-modal"]').exists()).toBe(false)
  })

  it('shows headline stats and CO2 embed codes/previews pointing at the public widget routes', () => {
    const wrapper = mountComponent({ show: true })

    expect(wrapper.find('[data-testid="group-share-stats-headline-embed"]').element.value).toContain('/group/stats/5')
    expect(wrapper.find('[data-testid="group-share-stats-headline-preview"]').attributes('src')).toBe('/group/stats/5')

    expect(wrapper.find('[data-testid="group-share-stats-co2-embed"]').element.value).toContain('/outbound/info/group/5/leaf')
    expect(wrapper.find('[data-testid="group-share-stats-co2-preview"]').attributes('src')).toBe('/outbound/info/group/5/leaf')
  })

  it('emits close when the modal hides', async () => {
    const wrapper = mountComponent({ show: true })
    await wrapper.findComponent(BModalStub).vm.$emit('hide')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
