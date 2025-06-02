const {test, expect} = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent, addDevice } = require('./utils')
const interruptHandler = require('./interrupt-handler')

test('Spare parts set as expected', async ({page, baseURL}) => {
  test.slow()
  interruptHandler.setCurrentTest('Spare parts set as expected')
  
  try {
    interruptHandler.checkInterrupted()
    await login(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const groupid = await createGroup(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const eventid = await createEvent(page, baseURL, groupid, true)
    interruptHandler.checkInterrupted()
    
    await approveEvent(page, baseURL, eventid)
    interruptHandler.checkInterrupted()
    
    await addDevice(page, baseURL, eventid, true, false, true, true)
    interruptHandler.checkInterrupted()

    // Should  see spare parts tick in summary.  Two copies because of mobile view.
    await expect(await page.locator('.spare-parts-tick').count()).toEqual(2);
  } finally {
    interruptHandler.reset()
  }
})

test('Spare parts not set unexpectedly', async ({page, baseURL}) => {
  test.slow()
  interruptHandler.setCurrentTest('Spare parts not set unexpectedly')
  
  try {
    interruptHandler.checkInterrupted()
    await login(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const groupid = await createGroup(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const eventid = await createEvent(page, baseURL, groupid, true)
    interruptHandler.checkInterrupted()
    
    await approveEvent(page, baseURL, eventid)
    interruptHandler.checkInterrupted()
    
    await addDevice(page, baseURL, eventid, true, false, true)
    interruptHandler.checkInterrupted()

    // Should not see spare parts tick in summary.
    await expect(await page.locator('.spare-parts-tick:visible').count()).toEqual(0);
  } finally {
    interruptHandler.reset()
  }
})

test('Can create misc powered device', async ({page, baseURL}) => {
  test.slow()
  interruptHandler.setCurrentTest('Can create misc powered device')
  
  try {
    interruptHandler.checkInterrupted()
    await login(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const groupid = await createGroup(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const eventid = await createEvent(page, baseURL, groupid, true)
    interruptHandler.checkInterrupted()
    
    await approveEvent(page, baseURL, eventid)
    interruptHandler.checkInterrupted()
    
    await addDevice(page, baseURL, eventid, true)
    interruptHandler.checkInterrupted()
  } finally {
    interruptHandler.reset()
  }
})

test('Can create device with photo', async ({page, baseURL}) => {
  test.slow()
  interruptHandler.setCurrentTest('Can create device with photo')
  
  try {
    interruptHandler.checkInterrupted()
    await login(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const groupid = await createGroup(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const eventid = await createEvent(page, baseURL, groupid, true)
    interruptHandler.checkInterrupted()
    
    await approveEvent(page, baseURL, eventid)
    interruptHandler.checkInterrupted()
    
    await addDevice(page, baseURL, eventid, true, true)
    interruptHandler.checkInterrupted()
  } finally {
    interruptHandler.reset()
  }
})

test('Automatic category suggestion from item type', async ({page, baseURL}) => {
  test.slow()
  interruptHandler.setCurrentTest('Automatic category suggestion from item type')
  
  try {
    interruptHandler.checkInterrupted()
    await login(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const groupid = await createGroup(page, baseURL)
    interruptHandler.checkInterrupted()
    
    const eventid = await createEvent(page, baseURL, groupid, true)
    interruptHandler.checkInterrupted()
    
    await approveEvent(page, baseURL, eventid)
    interruptHandler.checkInterrupted()

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

    for (const testCase of testCases) {
      interruptHandler.checkInterrupted()
      
      // First create a device with the specific item type and expected category using the fixture.
      // This will mean that there is data for the category autocomplete to work with.
      await addDevice(page, baseURL, eventid, testCase.powered, false, false, false, testCase.itemType, testCase.expectedCategory)
      
      // Then test the UI behavior for category autocomplete
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
  } finally {
    interruptHandler.reset()
  }
})
