import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupVolunteers from '../../../app/components/groups/GroupVolunteers.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import fr from '../../../i18n/locales/fr.json'
import clientEn from '../../../i18n/locales/client-en.json'
import clientFr from '../../../i18n/locales/client-fr.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupVolunteers, {
    props: { groupId: 5, ...props },
    global: {
      plugins: [i18n],
      stubs: GROUP_VIEW_STUBS,
    },
  })
}

// French-locale mount, used only to prove a value actually round-trips
// through t() (most English translations of these keys are identity
// strings, e.g. "Soldering" -> "Soldering", so an English-only assertion
// can't tell a real t() call apart from a raw passthrough that happens to
// look the same - see PublicProfileView.spec.js's mountViewFr for the same
// reasoning applied to role names).
function mountComponentFr(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'fr', messages: { fr: { ...fr, ...clientFr } } })

  return mount(GroupVolunteers, {
    props: { groupId: 5, ...props },
    global: {
      plugins: [i18n],
      stubs: GROUP_VIEW_STUBS,
    },
  })
}

// The API's Skill resource returns `skill_name`, not `name` (the field the
// component used to read, which rendered blank regardless of locale -
// GroupVolunteers.vue's skillNames() doc comment). "Publicising events" is
// a real skill name in the exported 143-key set, with a French translation
// that actually differs from the raw string, so it proves a genuine t()
// call rather than a passthrough that happens to look identical.
const VOLUNTEERS = [
  { id: 1, user: 10, name: 'Sam', host: true, image: null, skills: [{ id: 1, skill_name: 'Publicising events' }] },
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

  it('renders one row per volunteer with a host label for hosts only', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })

    expect(wrapper.find('[data-testid="group-volunteer-10"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-host-badge-10"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-host-badge-11"]').exists()).toBe(false)
  })

  it('renders the host indicator as plain text, not a badge/pill (gap 16)', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })

    const host = wrapper.find('[data-testid="group-volunteer-host-badge-10"]')
    expect(host.element.tagName).toBe('SPAN')
    expect(host.classes()).toContain('host-label')
  })

  // develop's GroupVolunteer.vue only bolds the name when the volunteer is
  // a host (:class="{ 'font-weight-bold': volunteer.host }") - it is not a
  // blanket style on every row, and the link itself is plain text-black,
  // not a teal hyperlink.
  it('bolds the volunteer name only for hosts, and renders it as plain text-black (not a teal link)', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })

    const hostLink = wrapper.find('[data-testid="group-volunteer-name-10"]')
    expect(hostLink.classes()).toContain('fw-bold')
    expect(hostLink.classes()).toContain('text-black')

    const nonHostLink = wrapper.find('[data-testid="group-volunteer-name-11"]')
    expect(nonHostLink.classes()).not.toContain('fw-bold')
    expect(nonHostLink.classes()).toContain('text-black')
  })

  it('shows a pencil edit-icon dropdown (not inline buttons) for editable rows (gap 3)', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS, canedit: true })

    expect(wrapper.find('[data-testid="group-volunteer-edit-11"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-volunteer-edit-11"] img').attributes('src')).toBe('/icons/edit_ico_green.svg')
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

  // The API's Skill resource returns `skill_name`; a prior version read
  // `s.name`, which doesn't exist on the response, so the tooltip rendered
  // blank in every locale regardless of translation.
  it('reads skill_name (the field the API actually returns), not name', () => {
    const wrapper = mountComponent({ volunteers: VOLUNTEERS })

    const tooltip = wrapper.find('[data-testid="group-volunteer-10"] .text-muted').attributes('title')
    expect(tooltip).not.toBe('')
    expect(tooltip).toContain('Publicising events')
  })

  // Skill names are real translation keys (Laravel's JSON lang files,
  // resolved via @lang() in develop), the same mechanism role names use -
  // see GroupVolunteers.vue's skillNames() doc comment. Assert against
  // French specifically: an English-only assertion can't distinguish a
  // real t() call from a raw passthrough when the English translation
  // happens to equal the raw string.
  it('translates skill names via i18n in French too, not a raw passthrough', () => {
    const wrapper = mountComponentFr({ volunteers: VOLUNTEERS })

    const tooltip = wrapper.find('[data-testid="group-volunteer-10"] .text-muted').attributes('title')
    expect(tooltip).toContain('Promouvoir des événements')
  })
})
