import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventShareStatsModal from '../../../app/components/events/EventShareStatsModal.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { BModalStub, GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

// events.share_stats_header/share_stats_message/headline_stats_dropdown/
// headline_stats_message/co2_equivalence_visualisation_dropdown/
// infographic_message/embed_code_header are new lang/en/events.php keys
// (gap 3) not yet in the generated client i18n JSON - overlaid inline per
// the established DashboardWhatsHappening.spec.js pattern.
function mountComponent(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        events: {
          ...en.events,
          share_stats_header: 'Share your stats',
          share_stats_message: 'Well done! On the {date} at {event_name} we repaired {number_devices} items.',
          headline_stats_dropdown: 'Headline stats',
          headline_stats_message: 'This widget shows the headline stats for your event',
          co2_equivalence_visualisation_dropdown: 'CO2 equivalence visualisation',
          infographic_message: 'An infographic of an easy-to-understand CO2 equivalent.',
          embed_code_header: 'Embed code',
        },
      },
    },
  })

  return mount(EventShareStatsModal, {
    props: { eventId: 7, eventDate: '1 Jan', eventName: 'Repair Cafe', fixedDevices: 4, ...props },
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

describe('components/events/EventShareStatsModal', () => {
  it('renders nothing when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-testid="event-share-stats-modal"]').exists()).toBe(false)
  })

  it('shows headline stats and CO2 embed codes/previews pointing at the public widget routes', () => {
    const wrapper = mountComponent({ show: true })

    expect(wrapper.find('[data-testid="event-share-stats-headline-embed"]').element.value).toContain('/party/stats/7/wide')
    expect(wrapper.find('[data-testid="event-share-stats-headline-preview"]').attributes('src')).toBe('/party/stats/7/wide')

    expect(wrapper.find('[data-testid="event-share-stats-co2-embed"]').element.value).toContain('/outbound/info/party/7/leaf')
    expect(wrapper.find('[data-testid="event-share-stats-co2-preview"]').attributes('src')).toBe('/outbound/info/party/7/leaf')
  })

  it('includes the share-stats message with the event date/name/fixed-device count', () => {
    const wrapper = mountComponent({ show: true, eventDate: '1 Jan 2026', eventName: 'Repair Cafe', fixedDevices: 4 })
    expect(wrapper.text()).toContain('1 Jan 2026')
    expect(wrapper.text()).toContain('Repair Cafe')
    expect(wrapper.text()).toContain('4')
  })

  it('emits close when the modal hides', async () => {
    const wrapper = mountComponent({ show: true })
    await wrapper.findComponent(BModalStub).vm.$emit('hide')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
