const {test, expect} = require('@playwright/test')
const { login, createGroup } = require('./utils')

test('Can create group', async ({page, baseURL}) => {
  await login(page, baseURL)
  await createGroup(page, baseURL)
})