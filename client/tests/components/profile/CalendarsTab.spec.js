import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CalendarsTab from '../../../app/components/profile/CalendarsTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }

const CALENDARS_RESPONSE = {
  user_url: 'https://example.test/calendar/user/abc',
  groups: [{ id: 1, name: 'Group One', url: 'https://example.test/calendar/group/1' }],
  is_admin: true,
  admin_all_events_url: 'https://example.test/calendar/all-events/xyz/',
  group_areas: ['London', 'Manchester'],
}

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(CalendarsTab, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub },
    },
  })
}

describe('components/profile/CalendarsTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
    useSessionStore().config = { discourse_url: 'https://talk.example' }
  })

  it('fetches GET /users/me/calendars on mount and renders the user/group/admin/area links', async () => {
    profileStore.fetchCalendars = vi.fn().mockResolvedValue(CALENDARS_RESPONSE)
    profileStore.calendars.data = CALENDARS_RESPONSE

    const wrapper = mountComponent()
    await flushPromises()

    expect(profileStore.fetchCalendars).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="calendars-user-url"]').element.value).toBe('https://example.test/calendar/user/abc')
    expect(wrapper.text()).toContain('Group One')
    // is_admin + admin_all_events_url both present -> the admin-only row renders.
    expect(wrapper.text()).toContain('All events (admin only)')
  })

  it('pre-selects the first group area and builds its URL', async () => {
    profileStore.fetchCalendars = vi.fn().mockResolvedValue(CALENDARS_RESPONSE)
    profileStore.calendars.data = CALENDARS_RESPONSE

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="calendars-area-select"]').element.value).toBe('London')
  })

  it('shows a load-error state when the fetch fails', async () => {
    profileStore.fetchCalendars = vi.fn().mockRejectedValue({ status: 500 })
    profileStore.calendars.error = { status: 500 }

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="calendars-error"]').exists()).toBe(true)
  })
})
