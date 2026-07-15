import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupAllPage from '../../../app/pages/group/all.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupAllPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub },
    },
  })
}

function namedGroups(count) {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    name: `Group ${String(i + 1).padStart(2, '0')}`,
    archived_at: null,
  }))
}

describe('pages/group/all', () => {
  let groupsStore

  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])
    groupsStore.fetchDetails = vi.fn().mockResolvedValue(null)
  })

  it('calls groupsStore.fetchNames() with includeArchived on mount', () => {
    mountPage()
    expect(groupsStore.fetchNames).toHaveBeenCalledWith({ includeArchived: 'true' })
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.namesLoading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-all-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button that calls fetchNames again', async () => {
    groupsStore.namesError = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-all-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-all-retry"]').trigger('click')
    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(2)
  })

  it('filters rows by the search box', async () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha Fixers', archived_at: null },
      { id: 2, name: 'Beta Fixers', archived_at: null },
    ]

    const wrapper = mountPage()
    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)

    await wrapper.find('[data-testid="group-all-search"]').setValue('Alpha')

    const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
    expect(rows).toHaveLength(1)
    expect(rows[0].attributes('data-testid')).toBe('group-row-1')
  })

  it('hides archived groups until the include-archived checkbox is ticked', async () => {
    groupsStore.names = [
      { id: 1, name: 'Active Group', archived_at: null },
      { id: 2, name: 'Old Group', archived_at: '2024-01-01T00:00:00Z' },
    ]

    const wrapper = mountPage()
    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(1)

    await wrapper.find('[data-testid="group-all-include-archived"]').setValue(true)

    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)
  })

  it('paginates 20 rows per page', async () => {
    groupsStore.names = namedGroups(25)

    const wrapper = mountPage()

    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(20)
    expect(wrapper.find('[data-testid="group-all-page-indicator"]').text()).toContain('1')
    expect(wrapper.find('[data-testid="group-all-page-indicator"]').text()).toContain('2')
    expect(wrapper.find('[data-testid="group-all-prev-page"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="group-all-next-page"]').trigger('click')

    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(5)
    expect(wrapper.find('[data-testid="group-all-next-page"]').attributes('disabled')).toBeDefined()
  })

  it('hydrates details for the rows on the current page', () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha Fixers', archived_at: null },
      { id: 2, name: 'Beta Fixers', archived_at: null },
    ]

    mountPage()

    expect(groupsStore.fetchDetails).toHaveBeenCalledWith(1)
    expect(groupsStore.fetchDetails).toHaveBeenCalledWith(2)
  })

  it('toggles an optional column off via the column preferences checkboxes', async () => {
    groupsStore.names = [{ id: 1, name: 'Alpha Fixers', archived_at: null }]

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="groups-table-sort-hosts"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-all-column-toggle-hosts"]').trigger('change')

    expect(wrapper.find('[data-testid="groups-table-sort-hosts"]').exists()).toBe(false)
  })
})
