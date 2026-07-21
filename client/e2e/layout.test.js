import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// A page must never scroll sideways. Uppy's Dashboard defaults to a fixed
// 750px, and on /profile/edit - where it sits in a narrow column - that
// pushed the document to 1657px against a 1440 viewport, giving the whole
// page a horizontal scrollbar.
//
// This is deliberately an e2e test rather than a unit test: the defect is
// computed layout, so jsdom cannot see it. The 166 component tests over
// TusImageUpload and its consumers all passed while the bug was live.
const VIEWPORT = { width: 1440, height: 980 }

// Logged-in pages that carry an upload widget or a wide table - the two
// things most likely to burst their container.
const PAGES = ['/profile/edit', '/profile', '/dashboard', '/fixometer']

test.describe('no horizontal overflow', () => {
  test.use({ viewport: VIEWPORT })

  for (const path of PAGES) {
    test(`${path} fits the viewport`, async ({ page }) => {
      await login(page, USERS.admin)
      await page.goto(path)
      await page.waitForLoadState('networkidle')

      const { scrollWidth, clientWidth, widest } = await page.evaluate(() => {
        const vw = document.documentElement.clientWidth
        let widest = null

        for (const el of document.querySelectorAll('*')) {
          const r = el.getBoundingClientRect()

          if (r.width > 0 && r.right > vw + 1 && (!widest || r.right > widest.right)) {
            widest = {
              right: Math.round(r.right),
              tag: el.tagName.toLowerCase(),
              cls: String(el.className || '').slice(0, 80),
            }
          }
        }

        return { scrollWidth: document.documentElement.scrollWidth, clientWidth: vw, widest }
      })

      // Naming the offender in the failure message - "1657 !== 1440" alone
      // sends you hunting through the DOM by hand, which is how this one
      // took a while to find.
      expect(
        scrollWidth,
        widest ? `overflow past ${clientWidth}px from <${widest.tag} class="${widest.cls}"> ending at ${widest.right}px` : ''
      ).toBeLessThanOrEqual(clientWidth)
    })
  }
})
