const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent} = require('./utils')

test('Can create future event', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, false)
  await approveEvent(page, baseURL, eventid)
})

test('Can create past event', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
})