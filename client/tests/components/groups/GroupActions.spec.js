import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupActions from '../../../app/components/groups/GroupActions.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupActions, {
    props: { groupId: 5, ...props },
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

describe('components/groups/GroupActions', () => {
  it('renders a single GROUP ACTIONS dropdown (gap 1)', () => {
    const wrapper = mountComponent()
    const dropdown = wrapper.find('[data-testid="group-actions-dropdown"]')
    expect(dropdown.exists()).toBe(true)
    expect(dropdown.text()).toContain('Group actions')
  })

  describe('editor menu (canedit=true)', () => {
    it('shows Edit, Add event, Invite, Share stats and Export as items (gap 1, 2)', () => {
      const wrapper = mountComponent({ canedit: true })

      expect(wrapper.find('[data-testid="group-actions-edit"]').attributes('href')).toBe('/group/edit/5')
      expect(wrapper.find('[data-testid="group-actions-add-event"]').attributes('href')).toBe('/party/create/5')
      expect(wrapper.find('[data-testid="group-actions-invite"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-actions-share-stats"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-actions-export"]').attributes('href')).toBe('/export/devices/group/5')
    })

    it('emits invite/share-stats/join/leave/archive on click', async () => {
      const wrapper = mountComponent({ canedit: true, isMember: false, canPerformArchive: true })

      await wrapper.find('[data-testid="group-actions-invite"]').trigger('click')
      expect(wrapper.emitted('invite')).toBeTruthy()

      await wrapper.find('[data-testid="group-actions-share-stats"]').trigger('click')
      expect(wrapper.emitted('share-stats')).toBeTruthy()

      await wrapper.find('[data-testid="group-actions-join"]').trigger('click')
      expect(wrapper.emitted('join')).toBeTruthy()

      await wrapper.find('[data-testid="group-actions-archive"]').trigger('click')
      expect(wrapper.emitted('archive')).toBeTruthy()
    })

    it('shows Leave instead of Join when isMember is true', () => {
      const wrapper = mountComponent({ canedit: true, isMember: true })
      expect(wrapper.find('[data-testid="group-actions-leave"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-actions-join"]').exists()).toBe(false)
    })

    it('hides Archive when canPerformArchive is false', () => {
      const wrapper = mountComponent({ canedit: true, canPerformArchive: false })
      expect(wrapper.find('[data-testid="group-actions-archive"]').exists()).toBe(false)
    })

    it('disables Archive when the group is already archived', () => {
      const wrapper = mountComponent({ canedit: true, canPerformArchive: true, archived: true })
      const item = wrapper.find('[data-testid="group-actions-archive"]')
      expect(item.classes()).toContain('disabled')
    })
  })

  describe('non-editor menu (canedit=false)', () => {
    it('hides Edit, Add event and Export', () => {
      const wrapper = mountComponent({ canedit: false })

      expect(wrapper.find('[data-testid="group-actions-edit"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-actions-add-event"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-actions-export"]').exists()).toBe(false)
    })

    it('shows Join when not a member, Invite/Leave when a member', () => {
      const notMember = mountComponent({ canedit: false, isMember: false })
      expect(notMember.find('[data-testid="group-actions-join"]').exists()).toBe(true)
      expect(notMember.find('[data-testid="group-actions-invite"]').exists()).toBe(false)

      const member = mountComponent({ canedit: false, isMember: true })
      expect(member.find('[data-testid="group-actions-invite"]').exists()).toBe(true)
      expect(member.find('[data-testid="group-actions-leave"]').exists()).toBe(true)
    })

    it('always shows Share group stats', () => {
      const wrapper = mountComponent({ canedit: false })
      expect(wrapper.find('[data-testid="group-actions-share-stats"]').exists()).toBe(true)
    })
  })
})
