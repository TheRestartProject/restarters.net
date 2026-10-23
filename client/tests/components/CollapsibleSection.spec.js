import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import CollapsibleSection from '../../app/components/CollapsibleSection.vue'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

// The toggle names itself ("Expand"/"Collapse"), so it needs translations.
const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

function mountSection(props = {}) {
  return mount(CollapsibleSection, {
    props,
    global: { plugins: [i18n] },
    slots: {
      title: '<h2>My Section</h2>',
      default: '<p data-testid="body">Body content</p>',
    },
  })
}

describe('components/CollapsibleSection', () => {
  // The toggle used to be a <span>: unreachable by keyboard, and aria-expanded
  // is ignored on an element whose role does not support it. Its only content
  // is a "+" glyph, so it also needed a name of its own.
  it('is a real button that names itself and reports its state', async () => {
    const wrapper = mountSection({ collapsedOnMobile: true })
    const toggle = wrapper.find('[data-testid="collapsible-toggle"]')

    expect(toggle.element.tagName).toBe('BUTTON')
    expect(toggle.attributes('aria-label')).toBe(clientEn.client.common.expand)
    expect(toggle.attributes('aria-expanded')).toBe('false')
    expect(toggle.attributes('aria-controls')).toBe(
      wrapper.find('[data-testid="collapsible-body"]').attributes('id')
    )

    await toggle.trigger('click')
    expect(toggle.attributes('aria-expanded')).toBe('true')
    expect(toggle.attributes('aria-label')).toBe(clientEn.client.common.collapse)
  })

  // The header row toggles too, so an unstopped click would fire both
  // handlers and cancel itself out.
  it('toggles once, not twice, when the button itself is clicked', async () => {
    const wrapper = mountSection({ collapsedOnMobile: true })

    await wrapper.find('[data-testid="collapsible-toggle"]').trigger('click')

    expect(wrapper.find('[data-testid="collapsible-body"]').classes()).not.toContain('d-none')
  })

  it('renders the title and body', () => {
    const w = mountSection()
    expect(w.text()).toContain('My Section')
    expect(w.find('[data-testid="body"]').exists()).toBe(true)
  })

  it('is expanded by default (body visible on all breakpoints)', () => {
    const w = mountSection()
    expect(w.find('[data-testid="collapsible-body"]').classes()).not.toContain('d-none')
    expect(w.find('[data-testid="collapsible-toggle"] img').attributes('src')).toBe('/images/minus-icon.svg')
  })

  it('toggles the body to mobile-collapsed (d-none d-md-block) and back', async () => {
    const w = mountSection()

    await w.find('[data-testid="collapsible-header"]').trigger('click')
    let body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).toContain('d-none')
    expect(body.classes()).toContain('d-md-block') // still shown on desktop
    expect(w.find('[data-testid="collapsible-toggle"] img').attributes('src')).toBe('/images/add-icon.svg')

    await w.find('[data-testid="collapsible-header"]').trigger('click')
    body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).not.toContain('d-none')
  })

  it('can start collapsed on mobile (desktop still shows the body)', () => {
    const w = mountSection({ collapsedOnMobile: true })
    const body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).toContain('d-none')
    expect(body.classes()).toContain('d-md-block')
    expect(w.find('[data-testid="collapsible-toggle"] img').attributes('src')).toBe('/images/add-icon.svg')
  })
})
