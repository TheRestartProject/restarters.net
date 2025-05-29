const interruptHandler = require('./interrupt-handler')

// Global setup for interrupt handling
module.exports = async function globalSetup() {
  // Set up global process handlers for graceful shutdown
  process.on('SIGTERM', async () => {
    console.log('🔄 Received SIGTERM - allowing Playwright to finish generating reports...')
    
    // Give Playwright time to generate reports
    setTimeout(() => {
      console.log('📊 Test reports should now be available')
      process.exit(0)
    }, 2000)
  })

  // Handle SIGINT (Ctrl+C) more gracefully
  process.on('SIGINT', async () => {
    console.log('\n🛑 Received SIGINT - initiating graceful shutdown...')
    interruptHandler.cleanup()
    
    // Give Playwright time to generate reports  
    setTimeout(() => {
      console.log('📊 Test reports should now be available')
      process.exit(0)
    }, 2000)
  })

  // Handle uncaught exceptions during abort
  process.on('uncaughtException', (error) => {
    if (error.message && error.message.includes('ABORTED:')) {
      console.log('🏁 Test run aborted by user - generating final reports...')
      // Don't log the error as it's expected
      return
    }
    
    // For other uncaught exceptions, log them
    console.error('Uncaught exception:', error)
  })

  console.log('🚀 Global interrupt handler setup complete')
}