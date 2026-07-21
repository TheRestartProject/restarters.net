import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// A page must never scroll sideways. Uppy's Dashboard defaults to a fixed
// 750px, and on /profile/edit - where it sits in a narrow column - that
// pushed the document to 1657px against a 1440 viewport.
//
// This is deliberately an e2e test rather than a unit test: the defect is
// computed layout, so jsdom cannot see it. The 166 component tests over
// TusImageUpload and its consumers all passed while that bug was live.
//
// MEASUREMENT. Two obvious choices are both wrong, and it took measuring
// both against a known-bad and a known-good page to settle:
//
//   documentElement.scrollWidth counts a wide element even when an ancestor
//   with overflow-x:auto is scrolling it, so a legitimate .table-responsive
//   reads as page overflow - /user/all measures 687 against a 390 viewport
//   while being perfectly well behaved.
//
//   window.scrollX (does the page actually move?) misses the real defect
//   entirely: something clips the page horizontally, so the unfixed Uppy
//   panel did not produce a scrollbar - its right-hand 217px was simply cut
//   off and unreachable, which is worse than a scrollbar, not better.
//
// body.scrollWidth is the one that separates them: 390 for the contained
// table, 1657 for the Uppy panel. Verified in both directions by reverting
// the fix.
const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 980 },
  // Mobile matters separately: /profile/edit's overflow came from a fixed-width
  // widget, and the reverse case - content that only bursts its container once
  // the column narrows - is invisible at desktop width.
  { name: 'mobile', width: 390, height: 844 },
]

const PAGES = [
  '/profile/edit',
  '/profile',
  '/dashboard',
  '/fixometer',
  // Wide tables inside .table-responsive: these must stay contained rather
  // than dragging the page sideways.
  '/user/all',
  '/category',
  '/device/search',
  '/group/all',
]

for (const vp of VIEWPORTS) {
  test.describe(`no horizontal page scroll (${vp.name})`, () => {
    test.use({ viewport: { width: vp.width, height: vp.height } })

    for (const path of PAGES) {
      test(`${path} does not scroll sideways`, async ({ page }) => {
        await login(page, USERS.admin)
        await page.goto(path)
        await page.waitForLoadState('networkidle')

        const { scrollWidth, clientWidth, offender } = await page.evaluate(() => {
          // Name the culprit, ignoring anything a scroll container already
          // clips - otherwise the report points at the innocent wide table
          // rather than whatever is actually pushing the page out.
          const vw = document.documentElement.clientWidth
          const clipped = (el) => {
            for (let n = el.parentElement; n && n !== document.documentElement; n = n.parentElement) {
              const ox = getComputedStyle(n).overflowX
              if (ox === 'auto' || ox === 'scroll' || ox === 'hidden') return true
            }
            return false
          }

          let offender = null
          for (const el of document.querySelectorAll('*')) {
            const r = el.getBoundingClientRect()
            if (r.width > 0 && r.right > vw + 1 && !clipped(el) && (!offender || r.right > offender.right)) {
              offender = {
                right: Math.round(r.right),
                tag: el.tagName.toLowerCase(),
                cls: String(el.className || '').slice(0, 80),
              }
            }
          }

          return {
            scrollWidth: document.body.scrollWidth,
            clientWidth: vw,
            offender,
          }
        })

        expect(
          scrollWidth,
          offender
            ? `content runs to ${scrollWidth}px in a ${clientWidth}px viewport; widest unclipped element is <${offender.tag} class="${offender.cls}"> ending at ${offender.right}px`
            : `content runs to ${scrollWidth}px in a ${clientWidth}px viewport`
        ).toBeLessThanOrEqual(clientWidth)
      })
    }
  })
}
