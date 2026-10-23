import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupJoinButton from '../../../app/components/groups/GroupJoinButton.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
// See profile/DeleteAccountTab.spec.js's own note: BModal's own
// data-testid="stub-modal" is overridden by the real component's bound
// data-testid attribute (fallthrough-attrs merge), so the modal is found by
// that testid, not the stub's.
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue" data-testid="stub-modal"><slot /></div>',
}

// groups.leave_group_confirm/leave_group_button_mobile/join_group_button_mobile
// are new keys (lang/en/groups.php) not yet re-exported to en.json by
// `php artisan translations:export-client` - injected here so this spec
// exercises the real copy rather than the untranslated key fallback.
function mountComponent(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        groups: {
          ...en.groups,
          leave_group_confirm: 'Please confirm that you want to unfollow this group.',
          leave_group_button_mobile: 'Unfollow',
          join_group_button_mobile: 'Follow',
        },
      },
    },
  })

  return mount(GroupJoinButton, {
    props: { groupId: 1, ...props },
    global: {
      plugins: [i18n],
      stubs: { BButton: BButtonStub, BModal: BModalStub },
    },
  })
}

describe('components/groups/GroupJoinButton', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows the join label and calls store.join() when not a member', async () => {
    const store = useGroupsStore()
    store.join = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ isMember: false })

    expect(wrapper.text()).toContain('Follow group')

    await wrapper.find('[data-testid="group-join-1"]').trigger('click')

    expect(store.join).toHaveBeenCalledWith(1)
  })

  it('shows the leave label and opens a confirm modal (not an immediate call to store.leave()) when a member', async () => {
    const store = useGroupsStore()
    store.leave = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ isMember: true })

    expect(wrapper.text()).toContain('Unfollow group')
    expect(wrapper.find('[data-testid="group-leave-confirm-1"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-leave-1"]').trigger('click')

    expect(store.leave).not.toHaveBeenCalled()
    const modal = wrapper.find('[data-testid="group-leave-confirm-1"]')
    expect(modal.exists()).toBe(true)
    expect(modal.text()).toContain('Please confirm that you want to unfollow this group.')
  })

  it('calls store.leave() only once the confirm modal is confirmed', async () => {
    const store = useGroupsStore()
    store.leave = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ isMember: true })
    await wrapper.find('[data-testid="group-leave-1"]').trigger('click')
    await wrapper.find('[data-testid="group-leave-confirm-button-1"]').trigger('click')

    expect(store.leave).toHaveBeenCalledWith(1)
  })

  it('closes the confirm modal without calling store.leave() on cancel', async () => {
    const store = useGroupsStore()
    store.leave = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ isMember: true })
    await wrapper.find('[data-testid="group-leave-1"]').trigger('click')

    const modal = wrapper.find('[data-testid="group-leave-confirm-1"]')
    await modal.find('button').trigger('click')

    expect(store.leave).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="group-leave-confirm-1"]').exists()).toBe(false)
  })

  it('disables the button while the request is pending, and re-enables after', async () => {
    const store = useGroupsStore()
    let resolveJoin
    store.join = vi.fn(
      () =>
        new Promise((resolve) => {
          resolveJoin = resolve
        })
    )

    const wrapper = mountComponent({ isMember: false })
    await wrapper.find('[data-testid="group-join-1"]').trigger('click')

    expect(wrapper.find('[data-testid="group-join-1"]').attributes('disabled')).toBeDefined()

    resolveJoin()
    await flushPromises()

    expect(wrapper.find('[data-testid="group-join-1"]').attributes('disabled')).toBeUndefined()
  })

  it('does not throw when the store action rejects (revert + toast already handled by the store)', async () => {
    const store = useGroupsStore()
    store.join = vi.fn().mockRejectedValue(new Error('nope'))

    const wrapper = mountComponent({ isMember: false })
    await wrapper.find('[data-testid="group-join-1"]').trigger('click')
    await flushPromises()

    expect(store.join).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="group-join-1"]').attributes('disabled')).toBeUndefined()
  })
})
