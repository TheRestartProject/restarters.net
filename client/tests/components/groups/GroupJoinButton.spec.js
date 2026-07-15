import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupJoinButton from '../../../app/components/groups/GroupJoinButton.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupJoinButton, {
    props: { groupId: 1, ...props },
    global: {
      plugins: [i18n],
      stubs: { BButton: BButtonStub },
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

  it('shows the leave label and calls store.leave() when a member', async () => {
    const store = useGroupsStore()
    store.leave = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ isMember: true })

    expect(wrapper.text()).toContain('Unfollow group')

    await wrapper.find('[data-testid="group-leave-1"]').trigger('click')

    expect(store.leave).toHaveBeenCalledWith(1)
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
