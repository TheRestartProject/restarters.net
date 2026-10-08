import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupsTableFilters from '../../../app/components/groups/GroupsTableFilters.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupsTableFilters, {
    props,
    global: { plugins: [i18n] },
  })
}

describe('components/groups/GroupsTableFilters', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchTags = vi.fn().mockResolvedValue([])
  })

  // Develop cut the bar back to name and tags (ece9aed10e): place search is
  // the map's own box, country is a coarser version of it, and the network
  // filter only means anything to someone who already knows the networks.
  it('renders only the name and tag fields', () => {
    const wrapper = mountComponent({ showTags: true })

    const testids = wrapper
      .find('[data-testid="groups-table-filters-fields"]')
      .findAll('[data-testid]')
      .map((el) => el.attributes('data-testid'))
      .filter((id) => /^groups-table-filter-(name|tags|location|country|network)$/.test(id))

    expect(testids).toEqual(['groups-table-filter-name', 'groups-table-filter-tags'])
  })

  it('hides the tag dropdown unless showTags is set', () => {
    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="groups-table-filter-tags"]').exists()).toBe(false)
  })

  it('only fetches tag options when showTags is set', () => {
    mountComponent({ showTags: false })
    expect(groupsStore.fetchTags).not.toHaveBeenCalled()

    mountComponent({ showTags: true })
    expect(groupsStore.fetchTags).toHaveBeenCalled()
  })

  it('offers tag options from the groups store as selectable chips, when shown', async () => {
    groupsStore.tags.data = [{ id: 5, name: 'Electronics' }]

    const wrapper = mountComponent({ showTags: true })
    await wrapper.find('[data-testid="groups-table-filter-tags-search"]').trigger('focus')

    expect(wrapper.find('[data-testid="groups-table-filter-tags-option-5"]').text()).toBe('Electronics')
  })

  it('emits update:filters with the current criteria as the fields change', async () => {
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('Fixers')

    const emitted = wrapper.emitted('update:filters').at(-1)[0]
    expect(emitted.name).toBe('Fixers')
    expect(emitted.tags).toEqual([])
  })

  // Restores legacy's vue-multiselect :multiple="true" on this field -
  // filters.tags is an array of selected tag ids, not a single value.
  it('emits filters.tags as an array of ids, supporting more than one tag', async () => {
    groupsStore.tags.data = [
      { id: 5, name: 'Electronics' },
      { id: 6, name: 'Textiles' },
    ]

    const wrapper = mountComponent({ showTags: true })
    await wrapper.find('[data-testid="groups-table-filter-tags-search"]').trigger('focus')
    await wrapper.find('[data-testid="groups-table-filter-tags-option-5"]').trigger('mousedown')
    await wrapper.find('[data-testid="groups-table-filter-tags-search"]').trigger('focus')
    await wrapper.find('[data-testid="groups-table-filter-tags-option-6"]').trigger('mousedown')

    const emitted = wrapper.emitted('update:filters').at(-1)[0]
    expect(emitted.tags).toEqual([5, 6])
  })
})
