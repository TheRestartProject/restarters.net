const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')
import faker from 'faker';

test('Spare parts set as expected', async ({page, baseURL}) => {
  // Need to set faker seed so that we get the same data each time for the screenshot.
  faker.seed(123);

  test.slow()
  await login(page, baseURL)
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05});
  const groupid = await createGroup(page, baseURL)
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05});
  const eventid = await createEvent(page, baseURL, groupid, true)
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05});
  await approveEvent(page, baseURL, eventid)
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05});
  await addDevice(page, baseURL, eventid, true, false, true, true)
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05});

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
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true)
})

test('Can create device with photo', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
  await addDevice(page, baseURL, eventid, true, true)
})
