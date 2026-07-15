import { expect } from '@playwright/test'

// Seeded by Taskfile's playwright:seed-data (same fixed users as the legacy
// suite): jane@bloggs.net (Admin), nc@test.net (NetworkCoordinator),
// host@test.net (Host), all with password passw0rd.
export const USERS = {
  admin: { email: 'jane@bloggs.net', password: 'passw0rd' },
  nc: { email: 'nc@test.net', password: 'passw0rd' },
  host: { email: 'host@test.net', password: 'passw0rd' },
}

export async function login(page, { email, password } = USERS.admin) {
  await page.goto('/login')
  await page.getByTestId('login-email').fill(email)
  await page.getByTestId('login-password').fill(password)
  await page.getByTestId('login-submit').click()
  await page.waitForURL('**/dashboard')
  await expect(page.getByTestId('nav-user-menu')).toBeVisible()
}

export async function logout(page) {
  await page.getByTestId('nav-user-menu').click()
  await page.getByTestId('nav-logout').click()
  // The navbar's logout handler navigates to /login, which uses the plain
  // layout (no navbar) — assert the login form itself.
  await page.waitForURL('**/login**')
  await expect(page.getByTestId('login-form')).toBeVisible()
}
