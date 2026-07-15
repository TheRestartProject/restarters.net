import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupsTable from '../../../app/components/groups/GroupsTable.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const GroupJoinButtonStub = {
  props: ['groupId', 'groupName', 'isMember'],
  template: '<button :data-testid="`stub-join-${groupId}`">{{ isMember ? "Leave" : "Join" }}</button>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupsTable, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, GroupJoinButton: GroupJoinButtonStub },
    },
  })
}

const rows = [
  { id: 1, name: 'Zeta Fixers', hosts: 2, restarters: 10, nextEvent: { start: '2026-08-01T10:00:00Z' } },
  { id: 2, name: 'Alpha Fixers', hosts: 5, restarters: 3, nextEvent: null },
  { id: 3, name: 'Middle Fixers', hosts: null, restarters: 7, nextEvent: { start: '2026-07-20T10:00:00Z' } },
]

describe('components/groups/GroupsTable', () => {
  it('shows an empty-state row when there are no groups', () => {
    const wrapper = mountComponent({ groups: [] })

    expect(wrapper.find('[data-testid="groups-table-empty"]').exists()).toBe(true)
  })

  it('renders one row per group, linked to its view page', () => {
    const wrapper = mountComponent({ groups: rows })

    expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-row-link-1"]').attributes('href')).toBe('/group/view/1')
  })

  it('defaults to sorting by name ascending', () => {
    const wrapper = mountComponent({ groups: rows })

    const names = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(names).toEqual(['group-row-2', 'group-row-3', 'group-row-1'])
  })

  it('clicking the name header again reverses the sort', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-name"]').trigger('click')

    const names = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(names).toEqual(['group-row-1', 'group-row-3', 'group-row-2'])
  })

  it('sorts by hosts numerically, with null hosts last', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-hosts"]').trigger('click')

    const order = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(order).toEqual(['group-row-1', 'group-row-2', 'group-row-3'])
  })

  it('sorts by next_event date, with no-event rows last', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-next_event"]').trigger('click')

    const order = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(order).toEqual(['group-row-3', 'group-row-1', 'group-row-2'])
  })

  it('hides optional columns per the optionalColumns prop', () => {
    const wrapper = mountComponent({
      groups: rows,
      optionalColumns: { location: false, hosts: false, restarters: false, next_event: false },
    })

    expect(wrapper.find('[data-testid="groups-table-sort-hosts"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-row-hosts-1"]').exists()).toBe(false)
  })

  it('hides the join column when showJoin is false', () => {
    const wrapper = mountComponent({ groups: rows, showJoin: false })

    expect(wrapper.find('[data-testid="stub-join-1"]').exists()).toBe(false)
  })

  it('shows a role badge only when showRole is true and the row has a role', () => {
    const wrapper = mountComponent({
      groups: [{ id: 9, name: 'Role Group', role: 3 }],
      showRole: true,
    })

    expect(wrapper.find('[data-testid="group-row-role-9"]').text()).toBe('Host')
  })

  it('shows an archived badge only for archived rows', () => {
    const wrapper = mountComponent({
      groups: [
        { id: 10, name: 'Active', archivedAt: null },
        { id: 11, name: 'Old', archivedAt: '2024-01-01T00:00:00Z' },
      ],
    })

    expect(wrapper.find('[data-testid="group-row-archived-10"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-row-archived-11"]').exists()).toBe(true)
  })
})
