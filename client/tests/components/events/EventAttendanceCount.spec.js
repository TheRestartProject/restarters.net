import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import EventAttendanceCount from '../../../app/components/events/EventAttendanceCount.vue'

function mountCount(props = {}) {
  return mount(EventAttendanceCount, { props: { count: 0, ...props } })
}

// Ports EventAttendanceCount.vue: the +/- stepper next to "Participants"/
// "Volunteers" on the event view page (gap 13).
describe('components/events/EventAttendanceCount', () => {
  it('renders plain text (no stepper) when not canedit', () => {
    const wrapper = mountCount({ count: 5, canedit: false, testid: 'my-count' })

    expect(wrapper.find('[data-testid="my-count"]').text()).toBe('5')
    expect(wrapper.find('[data-testid="my-count-inc"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="my-count-dec"]').exists()).toBe(false)
  })

  it('renders a +/- stepper when canedit, starting from the count prop', () => {
    const wrapper = mountCount({ count: 5, canedit: true, testid: 'my-count' })
    expect(wrapper.find('[data-testid="my-count-input"]').element.value).toBe('5')
  })

  it('increments and emits change', async () => {
    const wrapper = mountCount({ count: 5, canedit: true, testid: 'my-count' })

    await wrapper.find('[data-testid="my-count-inc"]').trigger('click')

    expect(wrapper.find('[data-testid="my-count-input"]').element.value).toBe('6')
    expect(wrapper.emitted('change')).toEqual([[6]])
  })

  it('decrements and emits change, but never below zero', async () => {
    const wrapper = mountCount({ count: 0, canedit: true, testid: 'my-count' })

    await wrapper.find('[data-testid="my-count-dec"]').trigger('click')

    expect(wrapper.find('[data-testid="my-count-input"]').element.value).toBe('0')
    expect(wrapper.emitted('change')).toBeFalsy()
  })

  it('emits change on direct input, clamping negative/invalid values to zero', async () => {
    const wrapper = mountCount({ count: 5, canedit: true, testid: 'my-count' })
    const input = wrapper.find('[data-testid="my-count-input"]')

    await input.setValue('12')
    expect(wrapper.emitted('change').at(-1)).toEqual([12])

    await input.setValue('-3')
    expect(wrapper.emitted('change').at(-1)).toEqual([0])
  })

  it('re-syncs to a new count prop (e.g. the parent reporting the server\'s actual value after a save)', async () => {
    const wrapper = mountCount({ count: 5, canedit: true, testid: 'my-count' })

    await wrapper.find('[data-testid="my-count-inc"]').trigger('click')
    expect(wrapper.find('[data-testid="my-count-input"]').element.value).toBe('6')

    // The prop only ever held its initial value (5) so far - the local +1
    // was purely internal. Setting it to a genuinely different value (not
    // simply "back to 5", which wouldn't register as a change) simulates
    // the parent later reporting a server-confirmed value that differs from
    // the optimistic local edit (e.g. a failed save reverting it).
    await wrapper.setProps({ count: 10 })
    expect(wrapper.find('[data-testid="my-count-input"]').element.value).toBe('10')
  })
})
