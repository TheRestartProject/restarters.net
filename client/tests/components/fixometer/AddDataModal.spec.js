import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AddDataModal from '../../../app/components/fixometer/AddDataModal.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}
const BFormSelectStub = {
  props: ['modelValue', 'options'],
  emits: ['update:modelValue'],
  template: '<select><option v-for="o in options" :key="o.value" :value="o.value">{{ o.text }}</option></select>',
}

function mountComponent(props = {}) {
  // devices.add_data_group/add_data_event/add_data_action_button and
  // groups.follow_group_first/create_event_first (gap fix - develop's own
  // AddDataModal.vue lang keys) aren't in the checked-in i18n/locales
  // fixtures yet (regenerated from lang/en/*.php by
  // `translations:export-client`, which this task deliberately doesn't
  // run) - overlay develop's exact values here, same as DeviceForm.spec.js.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        devices: {
          ...en.devices,
          add_data_group: 'Please select a group',
          add_data_event: 'Please select an event',
          add_data_action_button: 'Go to event',
        },
        groups: {
          ...en.groups,
          follow_group_first:
            'Repair data is added to community repair events. Please first follow a group and then choose an event in order to add repair data.',
          create_event_first: 'Please first create an event in order to add repair data.',
        },
      },
    },
  })

  return mount(AddDataModal, {
    props: { show: true, ...props },
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BModal: BModalStub, BFormSelect: BFormSelectStub },
    },
  })
}

describe('components/fixometer/AddDataModal', () => {
  let dashboardStore
  let eventsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    dashboardStore = useDashboardStore()
    eventsStore = useEventsStore()
    // These specs seed myEvents.data directly, i.e. they simulate a COMPLETED
    // fetch - so mark it loaded. Without this the page renders its loading
    // placeholder, because `loaded: false` now means "not fetched yet" rather
    // than "fetched and empty".
    eventsStore.myEvents.loaded = true
    dashboardStore.fetch = vi.fn().mockResolvedValue({})
    eventsStore.fetchMyEvents = vi.fn().mockResolvedValue({})
  })

  it('does not render when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
  })

  it('shows the "follow a group first" warning for a logged-out visitor, without fetching', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    expect(dashboardStore.fetch).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="add-data-no-groups"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="add-data-see-groups"]').attributes('href')).toBe('/group')
  })

  it('fetches groups/events once opened while logged in, and shows the group/event pickers', async () => {
    const authStore = useAuthStore()
    authStore.token = 'a-token'
    authStore.user = { id: 1, role_name: 'Restarter' }

    dashboardStore.data = { your_groups: [{ id: 1, name: 'Zeta Fixers' }, { id: 2, name: 'Alpha Repairs' }] }
    eventsStore.myEvents.data = [
      { id: 10, title: 'Alpha Event', group: { id: 2 }, start: '2026-01-01T10:00:00Z' },
    ]

    const wrapper = mountComponent()
    await flushPromises()

    expect(dashboardStore.fetch).toHaveBeenCalledTimes(1)
    expect(eventsStore.fetchMyEvents).toHaveBeenCalledTimes(1)

    // Sorted A-Z; defaults to the first (Alpha Repairs), which has an event.
    const groupOpts = wrapper.find('[data-testid="add-data-group"]').findAll('option')
    expect(groupOpts.map((o) => o.text())).toEqual(['Alpha Repairs', 'Zeta Fixers'])

    const goLink = wrapper.find('[data-testid="add-data-go"]')
    expect(goLink.attributes('href')).toBe('/party/view/10#devices-section')
  })

  it('shows a "no events yet" warning with a create-event link when the selected group has none', async () => {
    const authStore = useAuthStore()
    authStore.token = 'a-token'
    authStore.user = { id: 1, role_name: 'Restarter' }

    dashboardStore.data = { your_groups: [{ id: 1, name: 'Groupless Fixers' }] }
    eventsStore.myEvents.data = []

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="add-data-no-events"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="add-data-create-event"]').attributes('href')).toBe('/party/create/1')
  })

  it('only fetches once across repeated opens', async () => {
    const authStore = useAuthStore()
    authStore.token = 'a-token'
    authStore.user = { id: 1, role_name: 'Restarter' }

    const wrapper = mountComponent({ show: false })
    await wrapper.setProps({ show: true })
    await flushPromises()
    await wrapper.setProps({ show: false })
    await wrapper.setProps({ show: true })
    await flushPromises()

    expect(dashboardStore.fetch).toHaveBeenCalledTimes(1)
  })
})
