import { getCurrentInstance, onBeforeUnmount, ref } from 'vue'

// The save buttons' icon, as develop's SpinButton.vue: a spinner while saving
// (for at least half a second, so it registers), then a tick for five seconds
// if the save worked, then the button's own icon again.
export const MINIMUM_SPIN_MS = 500
export const DONE_MS = 5000

export function useSpinButton() {
  // 'idle' | 'spinning' | 'done'
  const state = ref('idle')
  let started = 0
  let timer = null

  function clear() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  function start() {
    clear()
    started = Date.now()
    state.value = 'spinning'
  }

  function finish(success) {
    clear()
    const wait = Math.max(0, MINIMUM_SPIN_MS - (Date.now() - started))

    timer = setTimeout(() => {
      if (success) {
        state.value = 'done'
        timer = setTimeout(() => {
          state.value = 'idle'
          timer = null
        }, DONE_MS)
      } else {
        state.value = 'idle'
        timer = null
      }
    }, wait)
  }

  if (getCurrentInstance()) {
    onBeforeUnmount(clear)
  }

  return { state, start, finish }
}
