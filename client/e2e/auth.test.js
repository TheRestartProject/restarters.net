import { test, expect } from './fixtures'
import { USERS, login, logout } from './utils'

test.describe('authentication', () => {
  test('can log in as admin and reach the dashboard', async ({ page }) => {
    await login(page, USERS.admin)

    // The dashboard/profile/admin links live inside the user dropdown,
    // mirroring the legacy navbar.
    await page.getByTestId('nav-user-menu').click()
    await expect(page.getByTestId('nav-dashboard')).toBeVisible()
    // Admins see the admin menu.
    await expect(page.getByTestId('nav-admin-menu')).toBeVisible()
  })

  test('host does not see the admin menu', async ({ page }) => {
    await login(page, USERS.host)
    await page.getByTestId('nav-user-menu').click()
    await expect(page.getByTestId('nav-dashboard')).toBeVisible()
    await expect(page.getByTestId('nav-admin-menu')).toHaveCount(0)
  })

  test('wrong password shows an error and stays on login', async ({ page }) => {
    await page.goto('/login')
    await page.getByTestId('login-email').fill(USERS.admin.email)
    await page.getByTestId('login-password').fill('not-the-password')
    await page.getByTestId('login-submit').click()

    await expect(page.getByTestId('login-error')).toBeVisible()
    expect(page.url()).toContain('/login')
  })

  test('protected route redirects to login and honours redirect after', async ({ page }) => {
    await page.goto('/dashboard')
    await page.waitForURL((url) => url.pathname === '/login' && url.searchParams.get('redirect') === '/dashboard')

    await page.getByTestId('login-email').fill(USERS.admin.email)
    await page.getByTestId('login-password').fill(USERS.admin.password)
    await page.getByTestId('login-submit').click()

    await page.waitForURL('**/dashboard')
  })

  test('can log out', async ({ page }) => {
    await login(page, USERS.admin)
    await logout(page)

    // Protected pages are inaccessible again.
    await page.goto('/dashboard')
    await page.waitForURL('**/login**')
  })

  test('can register a new account with consents', async ({ page }) => {
    const email = `e2e-${Date.now()}@restarters.test`

    await page.goto('/user/register')
    await page.getByTestId('register-name').fill('E2E Test User')
    await page.getByTestId('register-email').fill(email)
    await page.getByTestId('register-password').fill('longenough123')
    await page.getByTestId('register-password-confirmation').fill('longenough123')
    await page.getByTestId('register-age').selectOption({ index: 30 })
    await page.getByTestId('register-country').selectOption({ label: 'United Kingdom' })
    await page.getByTestId('register-consent-gdpr').check()
    await page.getByTestId('register-consent-future-data').check()
    await page.getByTestId('register-submit').click()

    await page.waitForURL('**/dashboard')
    await expect(page.getByTestId('nav-user-menu')).toBeVisible()
  })

  test('registration rejects a taken email', async ({ page }) => {
    await page.goto('/user/register')
    await page.getByTestId('register-name').fill('Duplicate User')
    await page.getByTestId('register-email').fill(USERS.admin.email)
    await page.getByTestId('register-password').fill('longenough123')
    await page.getByTestId('register-password-confirmation').fill('longenough123')
    await page.getByTestId('register-age').selectOption({ index: 30 })
    await page.getByTestId('register-country').selectOption({ label: 'United Kingdom' })
    await page.getByTestId('register-consent-gdpr').check()
    await page.getByTestId('register-consent-future-data').check()
    await page.getByTestId('register-submit').click()

    await expect(page.getByTestId('register-email-error')).toBeVisible()
    expect(page.url()).toContain('/user/register')
  })

  test('forgot password flow reports success for a known account', async ({ page }) => {
    await page.goto('/login')
    await page.getByTestId('login-forgot-password-link').click()
    await page.waitForURL('**/user/recover')

    await page.getByTestId('recover-email').fill(USERS.host.email)
    await page.getByTestId('recover-submit').click()

    await expect(page.getByTestId('recover-success')).toBeVisible()
  })
})
