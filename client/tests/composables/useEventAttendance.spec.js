import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useEventAttendance, EVENT_ROLE_HOST } from '../../app/composables/useEventAttendance.js'
import { useEventsStore } from '../../app/stores/events.js'

describe('composables/useEventAttendance', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('exposes confirmed/invited from the store, defaulting to empty arrays', () => {
    const { confirmed, invited } = useEventAttendance(5)
    expect(confirmed.value).toEqual([])
    expect(invited.value).toEqual([])
  })

  it('derives hosts as confirmed rows with role === HOST', () => {
    const store = useEventsStore()
    store.attendees.data = {
      confirmed: [
        { id: 1, role: EVENT_ROLE_HOST, user: 10 },
        { id: 2, role: 4, user: 11 },
        { id: 3, role: EVENT_ROLE_HOST, user: 12 },
      ],
      invited: [],
    }

    const { hosts } = useEventAttendance(5)
    expect(hosts.value.map((h) => h.id)).toEqual([1, 3])
  })

  it('isAttendingUser checks confirmed rows by user id', () => {
    const store = useEventsStore()
    store.attendees.data = {
      confirmed: [{ id: 1, user: 10 }],
      invited: [{ id: 2, user: 99 }],
    }

    const { isAttendingUser } = useEventAttendance(5)
    expect(isAttendingUser(10)).toBe(true)
    expect(isAttendingUser(99)).toBe(false)
    expect(isAttendingUser(12345)).toBe(false)
  })

  it('fetch() delegates to store.fetchAttendees(id)', () => {
    const store = useEventsStore()
    store.fetchAttendees = vi.fn().mockResolvedValue()

    const { fetch } = useEventAttendance(5)
    fetch()

    expect(store.fetchAttendees).toHaveBeenCalledWith(5)
  })

  it('remove() delegates to store.removeAttendee(id, idEventsUsers)', () => {
    const store = useEventsStore()
    store.removeAttendee = vi.fn().mockResolvedValue()

    const { remove } = useEventAttendance(5)
    remove(42)

    expect(store.removeAttendee).toHaveBeenCalledWith(5, 42)
  })

  it('reflects loading/error state from the store', () => {
    const store = useEventsStore()
    store.attendees.loading = true
    store.attendees.error = { status: 500 }

    const { loading, error } = useEventAttendance(5)
    expect(loading.value).toBe(true)
    expect(error.value).toEqual({ status: 500 })
  })
})
