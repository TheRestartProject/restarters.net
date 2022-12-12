const { test, expect } = require("playwright-test-coverage");
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')
const v8toIstanbul = require('v8-to-istanbul');
const fs = require('file-system');

test('Spare parts set as expected', async ({page, baseURL}) => {
  test.slow()
  await page.coverage.startJSCoverage({
    reportAnonymousScripts: true,
    resetOnNavigation: false
  });
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true, false, true, true)

  // Should  see spare parts tick in summary.  Two copies because of mobile view.
  await expect(await page.locator('.spare-parts-tick').count()).toEqual(2);

  const coverage = await page.coverage.stopJSCoverage();
  console.log('Got coverage')
  for (const entry of coverage) {
    const fn = entry.url.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    console.log(fn);
    const converter = v8toIstanbul('', 0, { source: entry.source });
    await converter.load();
    converter.applyCoverage(entry.functions);
    await fs.writeFile(fn + '.json', JSON.stringify(converter.toIstanbul()));
  }
})

test('Spare parts not set unexpectedly', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true, false, true)

  // Should not see spare parts tick in summary.
  await expect(await page.locator('.spare-parts-tick:visible').count()).toEqual(0);
})

test('Can create misc powered device', async ({page, baseURL}) => {
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true)
})

test('Can create device with photo', async ({page, baseURL}) => {
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true, true)
})
