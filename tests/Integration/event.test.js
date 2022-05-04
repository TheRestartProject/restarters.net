const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent } = require('./utils')

test('Can create event', async ({page, baseURL}) => {
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid)
})