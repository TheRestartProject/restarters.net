const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login, createGroup, unfollowGroup } = require('./utils')
const path = require('path')

test('Can create group', async ({page, baseURL}) => {
  test.slow()

  // Listen to browser console
  page.on('console', msg => {
    console.log('BROWSER CONSOLE:', msg.type(), msg.text())
  })

  await login(page, baseURL)
  await createGroup(page, baseURL)
})

test('Can unfollow group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const id = await createGroup(page, baseURL)
  await unfollowGroup(page, id)
})

test('Group image upload persists on view page', async ({page, baseURL}) => {
  test.slow()

  await login(page, baseURL)
  const id = await createGroup(page, baseURL)

  // createGroup redirects to the edit page — upload a group image there
  await page.waitForURL('**/edit/**')

  const fileChooserPromise = page.waitForEvent('filechooser')
  await page.locator('#dropzone').click()
  const fileChooser = await fileChooserPromise
  await fileChooser.setFiles(path.join('public', 'images', 'community.jpg'))

  // Wait for the dropzone preview to confirm the file was accepted
  await page.waitForSelector('#dropzone .dz-preview', { timeout: 10000 })

  // Save the group
  await page.locator('button', { hasText: 'Save changes' }).click()

  // Should redirect to the group view page
  await page.waitForURL('**/view/**', { timeout: 30000 })

  // The group heading image must resolve to an uploaded file, not the default profile icon
  await expect(page.locator('img.groupImage[src*="/uploads/"]')).toBeVisible({ timeout: 10000 })
})
