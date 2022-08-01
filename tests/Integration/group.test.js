const {test, expect} = require('@playwright/test')
const { login, createGroup, unfollowGroup } = require('./utils')

test('Can create group', async ({page, baseURL}) => {
  await login(page, baseURL)
  const id = await createGroup(page, baseURL)
})

test('Can unfollow group', async ({page, baseURL}) => {
  await login(page, baseURL)
  const id = await createGroup(page, baseURL)
  await unfollowGroup(page, baseURL, id)
})
