import { mount } from '@vue/test-utils'
import PlacesAutocomplete from './PlacesAutocomplete.vue'

// The server turns away a blank search with a 422.
describe('PlacesAutocomplete lookups', () => {
  beforeEach(() => {
    jest.useFakeTimers()
    global.fetch = jest.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({ predictions: [] }) }))
  })

  afterEach(() => {
    jest.useRealTimers()
  })

  function makeWrapper () {
    return mount(PlacesAutocomplete, { propsData: { id: 'location' } })
  }

  test('looks up what was typed, trimmed', async () => {
    const wrapper = makeWrapper()
    await wrapper.find('input').setValue('  Lon ')
    jest.advanceTimersByTime(300)

    expect(global.fetch).toHaveBeenCalledTimes(1)
    expect(global.fetch.mock.calls[0][0]).toContain('input=Lon&')
  })

  test('does not look up spaces', async () => {
    const wrapper = makeWrapper()
    await wrapper.find('input').setValue('    ')
    jest.advanceTimersByTime(300)

    expect(global.fetch).not.toHaveBeenCalled()
  })

  test('drops a lookup that was waiting when the value is set from outside', async () => {
    const wrapper = makeWrapper()
    await wrapper.find('input').setValue('London')
    wrapper.vm.update('')
    jest.advanceTimersByTime(300)

    expect(global.fetch).not.toHaveBeenCalled()
  })
})
