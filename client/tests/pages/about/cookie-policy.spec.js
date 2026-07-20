import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import CookiePolicyPage from '../../../app/pages/about/cookie-policy.vue'
import { useCookieConsent } from '../../../app/composables/useCookieConsent.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(CookiePolicyPage, {
    global: { plugins: [i18n] },
  })
}

describe('pages/about/cookie-policy', () => {
  it('renders the title and every cookie row', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="cookie-policy-title"]').text()).toBe('Cookie Policy')

    const table = wrapper.find('[data-testid="cookie-policy-table"]')
    expect(table.text()).toContain('restarters_session')
    expect(table.text()).toContain('XSRF-TOKEN')
    expect(table.text()).toContain('UseCDNCache, UseDC')
    expect(table.text()).toContain('_ga, _gat, _gid')
  })

  // Rendered-screenshot review: legacy's exact wording has no hyphen
  // ("third party cookies", not "third-party").
  it('says there are currently no third party cookies', () => {
    const wrapper = mountPage()
    expect(wrapper.text()).toContain('There are currently no third party cookies.')
  })

  // Gap 26: legacy wraps the cookie table in a visible `border: 1px solid gray` outline.
  it('gives the cookie table a visible border, matching legacy', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="cookie-policy-table"]').attributes('style')).toContain('border: 1px solid gray')
  })

  // Mobile parity capture (390x844): develop has an <h2> "How does
  // restarters.net use cookies?" between the "Managing cookies" paragraph
  // and the strictly-necessary/analytical/marketing bullet list - verified
  // verbatim against origin/develop's cookie-policy.blade.php. Was already
  // present in this page's markup/committed HEAD; pinned here so a future
  // regression shows up in vitest rather than only in a rendered screenshot.
  it('shows "How does restarters.net use cookies?" between "Managing cookies" and the cookie-type bullets', () => {
    const wrapper = mountPage()
    const headings = wrapper.findAll('h2, h3').map((h) => h.text())

    const managingIndex = headings.indexOf('Managing cookies')
    const howIndex = headings.indexOf('How does restarters.net use cookies?')
    expect(managingIndex).toBeGreaterThanOrEqual(0)
    expect(howIndex).toBe(managingIndex + 1)
  })

  // Rendered-screenshot review gap 5: a "The cookies we use" heading sits
  // directly above "Cookies set by us (first party)".
  it('shows "The cookies we use" heading directly above the cookie table section', () => {
    const wrapper = mountPage()
    const headings = wrapper.findAll('h2, h3').map((h) => h.text())

    const cookiesWeUseIndex = headings.indexOf('The cookies we use')
    const tableHeadingIndex = headings.indexOf('Cookies set by us (first party)')
    expect(cookiesWeUseIndex).toBeGreaterThanOrEqual(0)
    expect(tableHeadingIndex).toBe(cookiesWeUseIndex + 1)
  })

  // Rendered-screenshot review gap 9: the column header is "Cookie Name"
  // (Title Case), not "Cookie name".
  it('labels the first table column "Cookie Name"', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="cookie-policy-table"] thead th').text()).toBe('Cookie Name')
  })

  // Rendered-screenshot review gap 7: the _ga/_gat/_gid row's description is
  // develop's full paragraph, not a condensed one-sentence summary.
  it('shows the full Google Analytics cookie description, not a condensed summary', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="cookie-policy-table"]').text()).toContain(
      'Statistics derived from this information help us identify how well certain pages and aspects of the site perform'
    )
  })

  // Rendered-screenshot review gap 6: develop's table has 5 rows, including
  // restarters_session ("Used to keep you logged in between visits to the application.").
  it('includes the restarters_session row, and only 5 rows total', () => {
    const wrapper = mountPage()
    const table = wrapper.find('[data-testid="cookie-policy-table"]')
    expect(table.text()).toContain('restarters_session')
    expect(table.text()).toContain('Used to keep you logged in between visits to the application.')
    expect(wrapper.findAll('[data-testid="cookie-policy-table"] tbody tr').length).toBe(5)
  })

  // Rendered-screenshot review gap 4: develop's "persistent or session
  // cookies" section is a full intro paragraph plus a two-item bulleted
  // list (each with its own full paragraph), not one condensed sentence -
  // and gap 3's first/third-party definitions restored verbatim too.
  it('restores the full first/third-party and persistent/session cookie definitions, verbatim', () => {
    const wrapper = mountPage()
    const text = wrapper.text()

    expect(text).toContain('First Party.')
    expect(text).toContain('the website displayed in the address bar of your browser')
    expect(text).toContain('Third Party.')
    expect(text).toContain('Persistent cookies.')
    expect(text).toContain('remain on your device for the period of time specified in the cookie')
    expect(text).toContain('Session cookies.')
    expect(text).toContain('A browser session starts you open the browser window and finishes when you close the browser window')
  })

  // Rendered-screenshot review gap 3: the analytical/performance bullet
  // keeps develop's full explanation (not condensed), and each bullet's
  // bold lead-in matches develop's exact lowercase-start wording/punctuation.
  it('bullets the three cookie types with develop\'s exact bold lead-ins and full text', () => {
    const wrapper = mountPage()
    const bullets = wrapper.findAll('h2 + p + ul > li')
    const text = wrapper.text()

    expect(bullets[0].find('strong').text()).toBe('strictly necessary cookies.')
    expect(bullets[1].find('strong').text()).toBe('analytical/performance cookies')
    expect(bullets[2].find('strong').text()).toBe('marketing/targeting cookies')
    expect(text).toContain(
      'This helps us to improve the way our website works, for example, by ensuring that users are finding what they are looking for easily.'
    )
  })

  // Rendered-screenshot review gap 2: "aboutcookies.org.uk" and the cookie-
  // settings control are live links (not plain text), and the settings
  // control is inline in the "editing your cookie settings" sentence.
  it('links aboutcookies.org.uk as a real hyperlink', () => {
    const wrapper = mountPage()
    const link = wrapper.findAll('a').find((a) => a.text() === 'aboutcookies.org.uk')

    expect(link).toBeDefined()
    expect(link.attributes('href')).toBe('http://aboutcookies.org.uk/')
  })

  it('reopens the cookie-consent banner when the inline "cookie settings" link is clicked', async () => {
    const { accepted, accept } = useCookieConsent()
    accept()
    expect(accepted.value).toBe(true)

    const wrapper = mountPage()
    const link = wrapper.find('[data-testid="cookie-policy-reopen-settings"]')
    expect(link.text()).toBe('cookie settings')

    await link.trigger('click')

    expect(accepted.value).toBe(false)
  })
})
