import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { DONE_MS, MINIMUM_SPIN_MS, useSpinButton } from '../../app/composables/useSpinButton.js'

// As develop's SpinButton.vue: spin for at least half a second, then a tick for five seconds if the save worked.
describe('composables/useSpinButton', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('spins for at least the minimum time, ticks, then goes back to idle', () => {
    const spin = useSpinButton()
    spin.start()
    expect(spin.state.value).toBe('spinning')

    vi.advanceTimersByTime(100)
    spin.finish(true)
    expect(spin.state.value).toBe('spinning')

    vi.advanceTimersByTime(MINIMUM_SPIN_MS - 100)
    expect(spin.state.value).toBe('done')

    vi.advanceTimersByTime(DONE_MS)
    expect(spin.state.value).toBe('idle')
  })

  it('shows the tick at once when the save took longer than the minimum', () => {
    const spin = useSpinButton()
    spin.start()
    vi.advanceTimersByTime(MINIMUM_SPIN_MS + 200)
    spin.finish(true)
    vi.advanceTimersByTime(0)
    expect(spin.state.value).toBe('done')
  })

  it('goes straight back to idle, with no tick, when the save failed', () => {
    const spin = useSpinButton()
    spin.start()
    spin.finish(false)
    vi.advanceTimersByTime(MINIMUM_SPIN_MS)
    expect(spin.state.value).toBe('idle')
  })

  it('spins again if saved again while the tick shows', () => {
    const spin = useSpinButton()
    spin.start()
    spin.finish(true)
    vi.advanceTimersByTime(MINIMUM_SPIN_MS)
    expect(spin.state.value).toBe('done')

    spin.start()
    expect(spin.state.value).toBe('spinning')
    vi.advanceTimersByTime(DONE_MS)
    expect(spin.state.value).toBe('spinning')
  })
})
