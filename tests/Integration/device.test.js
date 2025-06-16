const {expect} = require('@playwright/test')
const { test } = require('./fixtures')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')

test('Spare parts set as expected', async ({page, baseURL}) => {
  test.slow()
  
  await login(page, baseURL)
  
  const groupid = await createGroup(page, baseURL)
  
  const eventid = await createEvent(page, baseURL, groupid, true)
  
  await approveEvent(page, baseURL, eventid)
  
  await addDevice(page, baseURL, eventid, true, false, true, true)

  // Should  see spare parts tick in summary.  Two copies because of mobile view.
  await expect(await page.locator('.spare-parts-tick').count()).toEqual(2);
})

test('Spare parts not set unexpectedly', async ({page, baseURL}) => {
  test.slow()
  
  await login(page, baseURL)
  
  const groupid = await createGroup(page, baseURL)
  
  const eventid = await createEvent(page, baseURL, groupid, true)
  
  await approveEvent(page, baseURL, eventid)
  
  await addDevice(page, baseURL, eventid, true, false, true)

  // Should not see spare parts tick in summary.
  await expect(await page.locator('.spare-parts-tick:visible').count()).toEqual(0);
})

test('Can create misc powered device', async ({page, baseURL}) => {
  test.slow()
  
  await login(page, baseURL)
  
  const groupid = await createGroup(page, baseURL)
  
  const eventid = await createEvent(page, baseURL, groupid, true)
  
  await approveEvent(page, baseURL, eventid)
  
  await addDevice(page, baseURL, eventid, true)
})

test('Can create device with photo', async ({page, baseURL}) => {
  test.slow()
  
  await login(page, baseURL)
  
  const groupid = await createGroup(page, baseURL)
  
  const eventid = await createEvent(page, baseURL, groupid, true)
  
  await approveEvent(page, baseURL, eventid)
  
  await addDevice(page, baseURL, eventid, true, true)
})

test('Automatic category suggestion from item type', async ({page, baseURL}) => {
  test.slow()
  
  await login(page, baseURL)
  
  const groupid = await createGroup(page, baseURL)
  
  const eventid = await createEvent(page, baseURL, groupid, true)
  
  await approveEvent(page, baseURL, eventid)

    // Test data: item types and their expected suggested categories
    const testCases = [
      { itemType: 'Food processor', expectedCategory: 'Small kitchen item', powered: true },
      { itemType: 'Blender', expectedCategory: 'Small kitchen item', powered: true },
      { itemType: 'TV', expectedCategory: 'Flat screen 32-37"', powered: true },
      { itemType: 'Phone', expectedCategory: 'Mobile', powered: true },
      { itemType: 'Printer', expectedCategory: 'Printer/scanner', powered: true },
      { itemType: 'Television', expectedCategory: 'Flat screen 32-37"', powered: true },
      { itemType: 'Télévision', expectedCategory: 'Flat screen 32-37"', powered: true },
      { itemType: 'Toaster', expectedCategory: 'Toaster', powered: true },
      { itemType: 'Microwave oven', expectedCategory: 'None of the above', powered: true },
      { itemType: 'Heater', expectedCategory: 'None of the above', powered: true }
    ]

    // Set up test data: create multiple devices for each item type to ensure autocomplete works
    // The getItemTypes() method uses a count-based algorithm, so we need sufficient data
    console.log('Setting up autocomplete test data...')
    
    let deviceCount = 0
    
    // Create the expected mappings (5 devices each to ensure they win the count algorithm)
    for (const testCase of testCases) {
      console.log(`Creating 5 devices for '${testCase.itemType}' → '${testCase.expectedCategory}'`)
      
      for (let i = 0; i < 5; i++) {
        await addDevice(page, baseURL, eventid, testCase.powered, false, false, false, testCase.itemType, testCase.expectedCategory)
        deviceCount++
      }
    }
    
    // Create some conflicting data with fewer items to ensure our expected categories win
    const conflictingData = [
      { itemType: 'Food processor', category: 'None of the above', count: 2 },
      { itemType: 'TV', category: 'Flat screen 15-17"', count: 3 },
      { itemType: 'Phone', category: 'Handheld entertainment device', count: 2 },
      { itemType: 'Printer', category: 'PC accessory', count: 1 },
      { itemType: 'Toaster', category: 'Small kitchen item', count: 2 }
    ]
    
    for (const conflict of conflictingData) {
      console.log(`Creating ${conflict.count} conflicting devices for '${conflict.itemType}' → '${conflict.category}'`)
      
      for (let i = 0; i < conflict.count; i++) {
        await addDevice(page, baseURL, eventid, true, false, false, false, conflict.itemType, conflict.category)
        deviceCount++
      }
    }
    
    console.log(`Created ${deviceCount} test devices successfully`)
    
    console.log('Testing autocomplete functionality...')
    // Note: The items.js store will automatically fetch fresh data since we're running under Playwright
    for (const testCase of testCases) {
      
      // Test the UI behavior for category autocomplete
      // Go to event view page
      await page.goto('/party/view/' + eventid)
      
      // Click the add powered device button (since all test cases are powered devices)
      await page.waitForSelector('.add-powered-device-desktop')
      await page.locator('.add-powered-device-desktop').click()

      // Wait for the device modal to open and item type field to be focused
      await page.waitForSelector('.device-select-row input', { state: 'visible' })
      
      // Type the item type
      await page.fill('.device-select-row input', testCase.itemType)

      // Select the suggestion by hitting tab and then enter
      await page.keyboard.press('Tab')
      await page.keyboard.press('Enter')
      
      // Wait for the category dropdown to be automatically populated with the expected category
      const categorySelect = page.locator('.device-category .multiselect__single').first()
      await expect(categorySelect).toContainText(testCase.expectedCategory)

      // Close the modal by clicking cancel to prepare for next test case
      await page.locator('.cancel', { hasText: 'Cancel' }).first().click()
    }
})
