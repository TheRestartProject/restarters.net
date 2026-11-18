import { defineComponent, h } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { MAP_CONTAINER_GONE, useLeafletUnmountGuard } from '../../app/composables/useLeafletUnmountGuard.js'

// A child that fails as vue-leaflet's <LMap> does when it is unmounted before
// its map is created.
const failingChild = (message) => defineComponent({
  async mounted() {
    await Promise.resolve()
    throw new Error(message)
  },
  render: () => h('div'),
})

function mountGuarded(message) {
  const errorHandler = vi.fn()
  const Host = defineComponent({
    setup() {
      useLeafletUnmountGuard()
      const Child = failingChild(message)
      return () => h('div', [h(Child)])
    },
  })
  mount(Host, { global: { config: { errorHandler } } })
  return errorHandler
}

describe('composables/useLeafletUnmountGuard', () => {
  it('stops Leaflet\'s error for a map that was unmounted before it was made', async () => {
    const errorHandler = mountGuarded(MAP_CONTAINER_GONE)
    await flushPromises()

    expect(errorHandler).not.toHaveBeenCalled()
  })

  it('lets any other error through', async () => {
    const errorHandler = mountGuarded('Something else broke.')
    await flushPromises()

    expect(errorHandler).toHaveBeenCalledTimes(1)
    expect(errorHandler.mock.calls[0][0].message).toBe('Something else broke.')
  })
})
