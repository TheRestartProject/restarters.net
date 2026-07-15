import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupMinePage from '../../../app/pages/group/index.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupMinePage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub },
    },
  })
}

describe('pages/group/index (mine)', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchMine = vi.fn().mockResolvedValue([])
  })

  it('calls groupsStore.fetchMine() on mount', () => {
    mountPage()
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.mine.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-mine-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-mine-table"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchMine again', async () => {
    groupsStore.mine.error = { status: 500 }

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-mine-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-mine-retry"]').trigger('click')
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(2)
  })

  it('shows the empty state, with a link to /group/nearby, when there are no groups', () => {
    groupsStore.mine.data = []

    const wrapper = mountPage()

    const empty = wrapper.find('[data-testid="group-mine-empty"]')
    expect(empty.exists()).toBe(true)
    expect(empty.html()).toContain('/group/nearby')
  })

  it('renders a row per group with role and a leave button', () => {
    groupsStore.mine.data = [{ id: 1, name: 'Fixers United', role: 3, archived: false, image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-row-role-1"]').text()).toBe('Host')
    expect(wrapper.find('[data-testid="group-leave-1"]').exists()).toBe(true)
  })

  it('shows an archived badge for archived groups', () => {
    groupsStore.mine.data = [{ id: 2, name: 'Old Group', role: 4, archived: true, image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-row-archived-2"]').exists()).toBe(true)
  })
})
