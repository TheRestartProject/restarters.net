import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupVolunteers from '../../../app/components/groups/GroupVolunteers.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupVolunteers, {
    props: { groupId: 5, ...props },
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
    },
  })
}

const VOLUNTEERS = [
  { id: 1, user: 10, name: 'Sam', host: true, image: null, skills: [{ id: 1, name: 'Soldering' }] },
  { id: 2, user: 11, name: 'Jo', host: false, image: null, skills: [] },
]

describe('components/groups/GroupVolunteers', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows the empty state when there are no volunteers', () => {
    const wrapper = mountComponent({ volunteers: [] })
    expect(wrapper.find('[data-testid="group-volunteers-empty"]').exists()).toBe(true)
  })

  it('renders one row per volunteer with a host badge for hosts only', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })

    expect(wrapper.find('[data-testid="group-volunteer-10"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-host-badge-10"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-host-badge-11"]').exists()).toBe(false)
  })

  it('hides manage actions when canedit is false', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS, canedit: false })

    expect(wrapper.find('[data-testid="group-volunteer-remove-10"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-volunteer-make-host-11"]').exists()).toBe(false)
  })

  it('shows make-host for non-hosts and remove-host only when candemote, when canedit is true', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS, canedit: true, candemote: false })

    expect(wrapper.find('[data-testid="group-volunteer-make-host-11"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-remove-host-10"]').exists()).toBe(false)

    const withDemote = mountComponent({ volunteers: VOLUNTEERS, canedit: true, candemote: true })
    expect(withDemote.find('[data-testid="group-volunteer-remove-host-10"]').exists()).toBe(true)
  })

  it('calls store.setVolunteerHost when make-host is clicked', async () => {
    const store = useGroupsStore()
    store.setVolunteerHost = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ volunteers: VOLUNTEERS, canedit: true })
    await wrapper.find('[data-testid="group-volunteer-make-host-11"]').trigger('click')

    expect(store.setVolunteerHost).toHaveBeenCalledWith(5, 11, true)
  })

  it('requires confirmation before calling store.removeVolunteer', async () => {
    const store = useGroupsStore()
    store.removeVolunteer = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ volunteers: VOLUNTEERS, canedit: true })
    await wrapper.find('[data-testid="group-volunteer-remove-11"]').trigger('click')

    expect(store.removeVolunteer).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="group-volunteer-remove-confirm-11"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-volunteer-remove-confirm-11"]').trigger('click')
    expect(store.removeVolunteer).toHaveBeenCalledWith(5, 11)
  })

  it('emits invite when the invite-to-group link is clicked', async () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })
    await wrapper.find('[data-testid="group-volunteers-invite-link"]').trigger('click')
    expect(wrapper.emitted('invite')).toBeTruthy()
  })
})
