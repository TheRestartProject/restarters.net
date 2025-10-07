const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login, createGroup, unfollowGroup } = require('./utils')

test('Can create group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  await createGroup(page, baseURL)
})

test('Can unfollow group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const id = await createGroup(page, baseURL)
  await unfollowGroup(page, id)
})
