const {test, expect} = require('@playwright/test')
const { login, createGroup } = require('./utils')

test('Can create group', async ({page, baseURL}) => {
  page = await login(page, baseURL)
  console.log("Logged in, now at" , page.url())
  await createGroup(page, baseURL)
})