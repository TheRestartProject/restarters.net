const {test, expect} = require('@playwright/test')
const {chromium} = require('playwright')
const v8toIstanbul = require('v8-to-istanbul')
const fs = require('fs')
const path = require('path')
const crypto = require("crypto");
const COVERAGE_DIRECTORY = '.nyc_output'

// We were hoping to be able to merge these using nyc report but it fails - ran out of time.  If we could,
// then we could upload to coveralls.

// test.beforeEach(async ({page}) => {
//   // Not available in all browsers.
//   if (page.coverage) {
//     await page.coverage.startJSCoverage()
//   }
// })
//
// test.afterEach(async ({page}) => {
//   if (page.coverage) {
//     if (!fs.existsSync(COVERAGE_DIRECTORY)) {
//       await fs.promises.mkdir(COVERAGE_DIRECTORY)
//     }
//
//     const coverage = await page.coverage.stopJSCoverage()
//     for (const entry of coverage) {
//       const converter = new v8toIstanbul('', 0, {source: entry.source})
//       await converter.load()
//       converter.applyCoverage(entry.functions)
//       const coverageJSON = JSON.stringify(converter.toIstanbul())
//       const uid = crypto.randomBytes(16).toString("hex");
//
//       fs.writeFileSync(path.join(COVERAGE_DIRECTORY, 'playwright_coverage_' + uid + '.json'), coverageJSON)
//     }
//   }
// })

test('Landing page has sign in', async ({page, baseURL}) => {
  // Simple test of page which is rendered with a Laravel blade.
  await page.goto(baseURL)
  const legend = page.locator('legend')
  await expect(legend).toHaveText('Sign in')
})
