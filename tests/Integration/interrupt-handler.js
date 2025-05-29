const readline = require('readline')

class TestInterruptHandler {
  constructor() {
    this.interrupted = false
    this.currentTest = null
    this.setupKeyListener()
  }

  setupKeyListener() {
    // Only set up keystroke listening if we're in an interactive terminal
    // and not in CI environment
    if (process.stdin.isTTY && !process.env.CI) {
      console.log('🔧 Playwright Test Interrupt Handler Active')
      console.log('📋 Press "q" + Enter to interrupt current test and fail it')
      console.log('📋 Press "s" + Enter to skip current test')
      console.log('📋 Press "a" + Enter to abort all remaining tests')
      console.log('----------------------------------------')

      // Set up readline interface
      this.rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout,
        terminal: false
      })

      // Listen for keystrokes
      this.rl.on('line', (input) => {
        const key = input.trim().toLowerCase()
        
        switch (key) {
          case 'q':
            this.interruptCurrentTest()
            break
          case 's':
            this.skipCurrentTest()
            break
          case 'a':
            this.abortAllTests()
            break
          default:
            if (key) {
              console.log(`❓ Unknown command "${key}". Use: q (quit test), s (skip test), a (abort all)`)
            }
        }
      })

      // Handle process termination
      process.on('SIGINT', () => {
        this.cleanup()
        process.exit(1)
      })
    }
  }

  setCurrentTest(testName) {
    this.currentTest = testName
    if (process.stdin.isTTY && !process.env.CI) {
      console.log(`🧪 Running test: ${testName}`)
    }
  }

  interruptCurrentTest() {
    if (this.currentTest) {
      console.log(`\n⚠️  Interrupting current test: ${this.currentTest}`)
      this.interrupted = true
      this.interruptReason = 'User interrupted test with "q" command'
    } else {
      console.log('\n⚠️  No active test to interrupt')
    }
  }

  skipCurrentTest() {
    if (this.currentTest) {
      console.log(`\n⏭️  Skipping current test: ${this.currentTest}`)
      this.interrupted = true
      this.interruptReason = 'User skipped test with "s" command'
      this.shouldSkip = true
    } else {
      console.log('\n⚠️  No active test to skip')
    }
  }

  abortAllTests() {
    console.log('\n🛑 Aborting all remaining tests...')
    console.log('📊 Generating test results before exit...')
    this.interrupted = true
    this.interruptReason = 'User aborted all tests with "a" command'
    this.shouldAbortAll = true
    
    // Set a flag that tests can check to fail gracefully
    this.globalAbort = true
    
    // Use graceful shutdown instead of immediate exit
    this.gracefulShutdown()
  }

  gracefulShutdown() {
    console.log('🔄 Initiating graceful shutdown to preserve test results...')
    
    // Send SIGTERM to allow Playwright to finish current operations and generate reports
    setTimeout(() => {
      console.log('📋 Test results should be available in the output directory')
      console.log('🏁 Shutdown complete')
      this.cleanup()
      
      // Use SIGTERM for graceful shutdown instead of hard exit
      process.kill(process.pid, 'SIGTERM')
    }, 100)
  }

  checkInterrupted() {
    if (this.globalAbort) {
      // For global abort, throw a special error that can be caught by test framework
      throw new Error(`ABORTED: All tests aborted by user command`)
    }
    
    if (this.interrupted) {
      const reason = this.interruptReason || 'Test was interrupted'
      this.reset()
      
      if (this.shouldSkip) {
        throw new Error(`SKIP: ${reason}`)
      } else {
        throw new Error(`INTERRUPTED: ${reason}`)
      }
    }
  }

  reset() {
    this.interrupted = false
    this.interruptReason = null
    this.shouldSkip = false
    this.currentTest = null
    // Don't reset globalAbort as it affects all remaining tests
  }

  cleanup() {
    if (this.rl) {
      this.rl.close()
    }
  }
}

// Export singleton instance
const interruptHandler = new TestInterruptHandler()
module.exports = interruptHandler