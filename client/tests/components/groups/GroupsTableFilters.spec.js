import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupsTableFilters from '../../../app/components/groups/GroupsTableFilters.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
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
  let networksStore
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    networksStore = useNetworksStore()
    groupsStore = useGroupsStore()
    networksStore.fetchList = vi.fn().mockResolvedValue([])
    groupsStore.fetchTags = vi.fn().mockResolvedValue([])
  })

  // gap #4: name (text) / tag (select) / location (text) / country (select)
  // / network (select), in that order - matching legacy's
  // GroupsTableFilters.vue field order exactly.
  it('renders the fields in legacy order: name, tag, location, country, network', () => {
    const wrapper = mountComponent({ showTags: true })

    const fields = wrapper.findAll('[data-testid="groups-table-filters-fields"] input, [data-testid="groups-table-filters-fields"] select')
    const testids = fields.map((el) => el.attributes('data-testid'))

    expect(testids).toEqual([
      'groups-table-filter-name',
      'groups-table-filter-tags',
      'groups-table-filter-location',
      'groups-table-filter-country',
      'groups-table-filter-network',
    ])
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

  it('fetches network options regardless of showTags - legacy shows the network dropdown to every user', () => {
    mountComponent()
    expect(networksStore.fetchList).toHaveBeenCalled()
  })

  it('populates the network dropdown from the networks store', () => {
    networksStore.list.data = [{ id: 1, name: 'UK Network' }, { id: 2, name: 'US Network' }]

    const wrapper = mountComponent()
    const options = wrapper.find('[data-testid="groups-table-filter-network"]').findAll('option')

    expect(options.map((o) => o.text())).toEqual(['Network', 'UK Network', 'US Network'])
  })

  it('populates the tag dropdown from the groups store, when shown', () => {
    groupsStore.tags.data = [{ id: 5, name: 'Electronics' }]

    const wrapper = mountComponent({ showTags: true })
    const options = wrapper.find('[data-testid="groups-table-filter-tags"]').findAll('option')

    // Placeholder text read from the locale file rather than duplicated here,
    // so a copy change in lang/ doesn't need a matching edit in this spec.
    expect(options.map((o) => o.text())).toEqual([en.groups.search_tags_placeholder, 'Electronics'])
  })

  // Country options are derived from the (unfiltered) `groups` prop, same
  // source legacy computes them from - not from a backend endpoint.
  it('derives unique, sorted country options from the groups prop', () => {
    const wrapper = mountComponent({
      groups: [
        { location: { country: 'UK' } },
        { location: { country: 'France' } },
        { location: { country: 'UK' } },
        { location: null },
      ],
    })
    const options = wrapper.find('[data-testid="groups-table-filter-country"]').findAll('option')

    expect(options.map((o) => o.text())).toEqual([en.groups.search_country_placeholder, 'France', 'UK'])
  })

  it('emits update:filters with the current criteria as the fields change', async () => {
    networksStore.list.data = [{ id: 1, name: 'UK Network' }]

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('Fixers')
    await wrapper.find('[data-testid="groups-table-filter-network"]').setValue('1')

    const emitted = wrapper.emitted('update:filters').at(-1)[0]
    expect(emitted.name).toBe('Fixers')
    expect(emitted.network).toBe(1)
  })
})
