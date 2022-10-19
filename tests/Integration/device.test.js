const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')

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
