const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')
import faker from 'faker';

test('Spare parts set as expected', async ({page, baseURL}) => {
  // Need to set faker seed so that we get the same data each time for the screenshot.
  faker.seed(123);

  test.slow()
  await login(page, baseURL)

  await expect(page.locator('.vue-placeholder-content:visible')).toHaveCount(0);
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05,maxDiffPixelRatio:0.2});

  const groupid = await createGroup(page, baseURL)

  await expect(page.locator('.vue-placeholder-content:visible')).toHaveCount(0);
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05,maxDiffPixelRatio:0.2});

  const eventid = await createEvent(page, baseURL, groupid, true)

  await expect(page.locator('.vue-placeholder-content:visible')).toHaveCount(0);
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05,maxDiffPixelRatio:0.2});

  await approveEvent(page, baseURL, eventid)

  await expect(page.locator('.vue-placeholder-content:visible')).toHaveCount(0);
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05,maxDiffPixelRatio:0.2});

  await addDevice(page, baseURL, eventid, true, false, true, true)

  await expect(page.locator('.vue-placeholder-content:visible')).toHaveCount(0);
  expect(await page.screenshot()).toMatchSnapshot({threshold:0.05,maxDiffPixelRatio:0.2});

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
