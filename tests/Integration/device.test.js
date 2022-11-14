const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')

test('Spare parts set as expected', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true, false, true, true)

  // Should  see spare parts tick in summary.  Two copies because of mobile view.
  await expect(await page.locator('.spare-parts-tick').count()).toEqual(2);
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
