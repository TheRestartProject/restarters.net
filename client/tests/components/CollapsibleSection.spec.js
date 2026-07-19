import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import CollapsibleSection from '../../app/components/CollapsibleSection.vue'

function mountSection(props = {}) {
  return mount(CollapsibleSection, {
    props,
    slots: {
      title: '<h2>My Section</h2>',
      default: '<p data-testid="body">Body content</p>',
    },
  })
}

describe('components/CollapsibleSection', () => {
  it('renders the title and body', () => {
    const w = mountSection()
    expect(w.text()).toContain('My Section')
    expect(w.find('[data-testid="body"]').exists()).toBe(true)
  })

  it('is expanded by default (body visible on all breakpoints)', () => {
    const w = mountSection()
    expect(w.find('[data-testid="collapsible-body"]').classes()).not.toContain('d-none')
    expect(w.find('[data-testid="collapsible-toggle"]').text()).toBe('−')
  })

  it('toggles the body to mobile-collapsed (d-none d-md-block) and back', async () => {
    const w = mountSection()

    await w.find('[data-testid="collapsible-header"]').trigger('click')
    let body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).toContain('d-none')
    expect(body.classes()).toContain('d-md-block') // still shown on desktop
    expect(w.find('[data-testid="collapsible-toggle"]').text()).toBe('+')

    await w.find('[data-testid="collapsible-header"]').trigger('click')
    body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).not.toContain('d-none')
  })

  it('can start collapsed on mobile (desktop still shows the body)', () => {
    const w = mountSection({ collapsedOnMobile: true })
    const body = w.find('[data-testid="collapsible-body"]')
    expect(body.classes()).toContain('d-none')
    expect(body.classes()).toContain('d-md-block')
    expect(w.find('[data-testid="collapsible-toggle"]').text()).toBe('+')
  })
})
