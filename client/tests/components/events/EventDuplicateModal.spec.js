import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventDuplicateModal from '../../../app/components/events/EventDuplicateModal.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { BModalStub, BButtonStub, BBadgeStub, NuxtLinkStub } from '../../helpers/stubs.js'

const match = (over = {}) => ({
  confidence: 'certain',
  reasons: ['same-time-and-place'],
  event: {
    id: 42,
    title: 'Repair Cafe',
    location: 'Brixton Library, London',
    online: false,
    start: '2026-10-10T13:00:00+00:00',
    end: '2026-10-10T16:00:00+00:00',
    timezone: 'Europe/London',
    ...over.event,
  },
  ...over,
})

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventDuplicateModal, {
    props: { show: true, matches: [match()], ...props },
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BButton: BButtonStub, BBadge: BBadgeStub, NuxtLink: NuxtLinkStub },
    },
  })
}

// The host is mid-create and about to add a second copy of an event that is
// already there. This never blocks - it shows what already exists and lets them
// choose: edit that one, or say it really is a different event.
describe('components/events/EventDuplicateModal', () => {
  it('renders nothing when show is false', () => {
    const wrapper = mountComponent({ show: false })

    expect(wrapper.find('[data-testid="event-duplicate-modal"]').exists()).toBe(false)
  })

  it('shows what already exists, so the host can recognise it', () => {
    const wrapper = mountComponent()
    const row = wrapper.find('[data-testid="event-duplicate-match-42"]')

    expect(row.exists()).toBe(true)
    expect(row.text()).toContain('Repair Cafe')
    expect(row.text()).toContain('Brixton Library, London')
  })

  it('links to editing the event that already exists', () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="event-duplicate-edit-42"]').attributes('href')).toBe('/party/edit/42')
  })

  it('lists every match, strongest first', () => {
    const wrapper = mountComponent({
      matches: [match(), match({ confidence: 'possible', event: { id: 7, title: 'Evening session' } })],
    })

    expect(wrapper.findAll('[data-testid^="event-duplicate-match-"]')).toHaveLength(2)
    expect(wrapper.find('[data-testid="event-duplicate-match-7"]').exists()).toBe(true)
  })

  it('says how sure it is, differently per match', () => {
    const wrapper = mountComponent({
      matches: [match(), match({ confidence: 'possible', event: { id: 7 } })],
    })

    const certain = wrapper.find('[data-testid="event-duplicate-confidence-42"]').text()
    const possible = wrapper.find('[data-testid="event-duplicate-confidence-7"]').text()

    expect(certain).not.toBe('')
    expect(possible).not.toBe('')
    expect(certain).not.toBe(possible)
  })

  it('emits post-anyway when the host says it is a different event', async () => {
    const wrapper = mountComponent()

    await wrapper.find('[data-testid="event-duplicate-post-anyway"]').trigger('click')

    expect(wrapper.emitted('post-anyway')).toHaveLength(1)
    expect(wrapper.emitted('close')).toBeUndefined()
  })

  it('emits close when the host backs out', async () => {
    const wrapper = mountComponent()

    await wrapper.find('[data-testid="event-duplicate-cancel"]').trigger('click')

    expect(wrapper.emitted('close')).toHaveLength(1)
    expect(wrapper.emitted('post-anyway')).toBeUndefined()
  })

  it('describes an online event as online rather than showing a blank venue', () => {
    const wrapper = mountComponent({
      matches: [match({ event: { id: 9, online: true, location: '' } })],
    })

    expect(wrapper.find('[data-testid="event-duplicate-match-9"]').text()).not.toBe('')
  })
})
