import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useEventsStore } from '../../app/stores/events.js'
import { useToastStore } from '../../app/stores/toast.js'

describe('stores/events', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      event: {
        myEvents: vi.fn(),
        attend: vi.fn(),
        unattend: vi.fn(),
        get: vi.fn(),
        attendees: vi.fn(),
        devices: vi.fn(),
        invite: vi.fn(),
        del: vi.fn(),
        removeVolunteer: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
        uploadImage: vi.fn(),
        addVolunteer: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('starts empty, not loading, no error', () => {
    const store = useEventsStore()
    expect(store.myEvents.data).toEqual([])
    expect(store.myEvents.loading).toBe(false)
    expect(store.myEvents.error).toBeNull()
  })

  describe('fetchMyEvents', () => {
    it('sets loading while in flight and populates data on success', async () => {
      const events = [{ id: 1, title: 'Event 1', attending: false }]

      let resolveFetch
      mockApi.event.myEvents.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        })
      )

      const store = useEventsStore()
      const promise = store.fetchMyEvents()

      expect(store.myEvents.loading).toBe(true)

      resolveFetch({ data: events })
      const result = await promise

      expect(store.myEvents.loading).toBe(false)
      expect(store.myEvents.error).toBeNull()
      expect(store.myEvents.data).toEqual(events)
      expect(result).toEqual(events)
    })

    it('sets error and rethrows on failure, leaving loading false', async () => {
      const apiError = { status: 500, message: 'Server error' }
      mockApi.event.myEvents.mockRejectedValueOnce(apiError)

      const store = useEventsStore()

      await expect(store.fetchMyEvents()).rejects.toEqual(apiError)

      expect(store.myEvents.loading).toBe(false)
      expect(store.myEvents.error).toEqual(apiError)
      expect(store.myEvents.data).toEqual([])
    })

    it('clears a previous error on a fresh call', async () => {
      mockApi.event.myEvents.mockRejectedValueOnce({ status: 500 })
      const store = useEventsStore()
      await expect(store.fetchMyEvents()).rejects.toBeTruthy()
      expect(store.myEvents.error).not.toBeNull()

      mockApi.event.myEvents.mockResolvedValueOnce({ data: [] })
      await store.fetchMyEvents()

      expect(store.myEvents.error).toBeNull()
    })
  })

  describe('attend', () => {
    it('optimistically flips attending to true and keeps it on success', async () => {
      mockApi.event.attend.mockResolvedValueOnce({
        data: { attending: true, already_attending: false, prompt_follow_group: false },
      })

      const store = useEventsStore()
      store.myEvents.data = [{ id: 5, title: 'Event 5', attending: false }]

      const promise = store.attend(5)

      expect(store.myEvents.data[0].attending).toBe(true)

      await promise

      expect(store.myEvents.data[0].attending).toBe(true)
    })

    it('reverts attending and pushes a toast on failure', async () => {
      const apiError = { status: 500, message: 'Nope' }
      mockApi.event.attend.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      store.myEvents.data = [{ id: 5, title: 'Event 5', attending: false }]
      const toastStore = useToastStore()

      await expect(store.attend(5)).rejects.toEqual(apiError)

      expect(store.myEvents.data[0].attending).toBe(false)
      expect(toastStore.toasts).toHaveLength(1)
      expect(toastStore.toasts[0].variant).toBe('danger')
    })

    it('is a no-op on myEvents.data when the event is not in the list', async () => {
      mockApi.event.attend.mockResolvedValueOnce({ data: { attending: true } })

      const store = useEventsStore()
      store.myEvents.data = [{ id: 1, title: 'Other', attending: false }]

      await store.attend(99)

      expect(store.myEvents.data).toEqual([{ id: 1, title: 'Other', attending: false }])
    })
  })

  describe('unattend', () => {
    it('optimistically flips attending to false and keeps it on success', async () => {
      mockApi.event.unattend.mockResolvedValueOnce({ data: { left: true } })

      const store = useEventsStore()
      store.myEvents.data = [{ id: 5, title: 'Event 5', attending: true }]

      const promise = store.unattend(5)

      expect(store.myEvents.data[0].attending).toBe(false)

      await promise

      expect(store.myEvents.data[0].attending).toBe(false)
    })

    it('reverts attending and pushes a toast on failure', async () => {
      const apiError = { status: 500, message: 'Nope' }
      mockApi.event.unattend.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      store.myEvents.data = [{ id: 5, title: 'Event 5', attending: true }]
      const toastStore = useToastStore()

      await expect(store.unattend(5)).rejects.toEqual(apiError)

      expect(store.myEvents.data[0].attending).toBe(true)
      expect(toastStore.toasts).toHaveLength(1)
      expect(toastStore.toasts[0].variant).toBe('danger')
    })
  })

  describe('attend/unattend also sync `current` when it is the viewed event', () => {
    it('attend flips current.data.attending and reverts it on failure', async () => {
      const store = useEventsStore()
      store.current.data = { id: 5, title: 'Event 5', attending: false }

      const apiError = { status: 500 }
      mockApi.event.attend.mockRejectedValueOnce(apiError)
      await expect(store.attend(5)).rejects.toEqual(apiError)
      expect(store.current.data.attending).toBe(false)

      mockApi.event.attend.mockResolvedValueOnce({ data: { attending: true } })
      await store.attend(5)
      expect(store.current.data.attending).toBe(true)
    })

    it('unattend flips current.data.attending and reverts it on failure', async () => {
      const store = useEventsStore()
      store.current.data = { id: 5, title: 'Event 5', attending: true }

      const apiError = { status: 500 }
      mockApi.event.unattend.mockRejectedValueOnce(apiError)
      await expect(store.unattend(5)).rejects.toEqual(apiError)
      expect(store.current.data.attending).toBe(true)

      mockApi.event.unattend.mockResolvedValueOnce({ data: { left: true } })
      await store.unattend(5)
      expect(store.current.data.attending).toBe(false)
    })

    it('does not touch current.data when it is a different event', async () => {
      const store = useEventsStore()
      store.current.data = { id: 99, title: 'Other event', attending: false }
      mockApi.event.attend.mockResolvedValueOnce({ data: { attending: true } })

      await store.attend(5)

      expect(store.current.data).toEqual({ id: 99, title: 'Other event', attending: false })
    })
  })

  describe('fetchEvent', () => {
    it('sets loading while in flight and populates current.data on success', async () => {
      let resolveFetch
      mockApi.event.get.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        })
      )

      const store = useEventsStore()
      const promise = store.fetchEvent(5)

      expect(store.current.loading).toBe(true)

      resolveFetch({ data: { id: 5, title: 'Event 5' } })
      await promise

      expect(store.current.loading).toBe(false)
      expect(store.current.error).toBeNull()
      expect(store.current.data).toEqual({ id: 5, title: 'Event 5' })
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 404 }
      mockApi.event.get.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      await expect(store.fetchEvent(5)).rejects.toEqual(apiError)

      expect(store.current.loading).toBe(false)
      expect(store.current.error).toEqual(apiError)
    })
  })

  describe('fetchAttendees', () => {
    it('populates attendees.data on success', async () => {
      const data = { confirmed: [{ id: 1 }], invited: [{ id: 2 }] }
      mockApi.event.attendees.mockResolvedValueOnce({ data })

      const store = useEventsStore()
      await store.fetchAttendees(5)

      expect(store.attendees.data).toEqual(data)
      expect(store.attendees.loading).toBe(false)
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.event.attendees.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      await expect(store.fetchAttendees(5)).rejects.toEqual(apiError)
      expect(store.attendees.error).toEqual(apiError)
    })
  })

  describe('fetchDevices', () => {
    it('populates devices.data on success', async () => {
      mockApi.event.devices.mockResolvedValueOnce({ data: [{ id: 1 }] })

      const store = useEventsStore()
      await store.fetchDevices(5)

      expect(store.devices.data).toEqual([{ id: 1 }])
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.event.devices.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      await expect(store.fetchDevices(5)).rejects.toEqual(apiError)
      expect(store.devices.error).toEqual(apiError)
    })
  })

  describe('inviteVolunteers', () => {
    it('returns the response data and leaves errors for the caller', async () => {
      mockApi.event.invite.mockResolvedValueOnce({ data: { invites_sent: 2, invalid: [] } })

      const store = useEventsStore()
      const result = await store.inviteVolunteers(5, { emails: ['a@example.com'] })

      expect(mockApi.event.invite).toHaveBeenCalledWith(5, { emails: ['a@example.com'] })
      expect(result).toEqual({ invites_sent: 2, invalid: [] })
    })

    it('rethrows on failure without toasting (left for the modal to render inline)', async () => {
      const apiError = { status: 422 }
      mockApi.event.invite.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const toastStore = useToastStore()

      await expect(store.inviteVolunteers(5, { emails: [] })).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  // Gap 14: PUT /api/events/{id}/volunteers (v1 - see api/EventAPI.js's
  // addVolunteer doc comment). Its response envelope is {success:'success'}
  // rather than v2's {data:...}, so this returns the raw response,
  // unlike inviteVolunteers above.
  describe('addVolunteer', () => {
    it('passes the payload through and returns the raw response', async () => {
      mockApi.event.addVolunteer.mockResolvedValueOnce({ success: 'success' })

      const store = useEventsStore()
      const result = await store.addVolunteer(5, { user: 20, full_name: null, volunteer_email_address: null })

      expect(mockApi.event.addVolunteer).toHaveBeenCalledWith(5, { user: 20, full_name: null, volunteer_email_address: null })
      expect(result).toEqual({ success: 'success' })
    })

    it('rethrows on failure without toasting (left for the modal to render inline)', async () => {
      const apiError = { status: 403 }
      mockApi.event.addVolunteer.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const toastStore = useToastStore()

      await expect(store.addVolunteer(5, { user: 20 })).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('deleteEvent', () => {
    it('returns the response data on success', async () => {
      mockApi.event.del.mockResolvedValueOnce({ data: { deleted: true } })

      const store = useEventsStore()
      const result = await store.deleteEvent(5)

      expect(mockApi.event.del).toHaveBeenCalledWith(5)
      expect(result).toEqual({ deleted: true })
    })

    it('toasts and rethrows on failure', async () => {
      const apiError = { status: 403 }
      mockApi.event.del.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const toastStore = useToastStore()

      await expect(store.deleteEvent(5)).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('removeAttendee', () => {
    it('optimistically removes the row from confirmed and keeps it removed on success', async () => {
      mockApi.event.removeVolunteer.mockResolvedValueOnce({ data: { removed: true } })

      const store = useEventsStore()
      store.attendees.data = { confirmed: [{ id: 1 }, { id: 2 }], invited: [] }

      const promise = store.removeAttendee(5, 1)
      expect(store.attendees.data.confirmed).toEqual([{ id: 2 }])

      await promise
      expect(store.attendees.data.confirmed).toEqual([{ id: 2 }])
      expect(mockApi.event.removeVolunteer).toHaveBeenCalledWith(5, 1)
    })

    it('reverts and toasts on failure', async () => {
      const apiError = { status: 500 }
      mockApi.event.removeVolunteer.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const original = { confirmed: [{ id: 1 }, { id: 2 }], invited: [{ id: 3 }] }
      store.attendees.data = original
      const toastStore = useToastStore()

      await expect(store.removeAttendee(5, 1)).rejects.toEqual(apiError)

      expect(store.attendees.data).toEqual(original)
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('createEvent', () => {
    it('returns the bare id from POST /api/v2/events (no {data:...} envelope)', async () => {
      mockApi.event.create.mockResolvedValueOnce({ id: 42 })

      const store = useEventsStore()
      const id = await store.createEvent({ title: 'New Event' })

      expect(mockApi.event.create).toHaveBeenCalledWith({ title: 'New Event' })
      expect(id).toBe(42)
    })

    it('rethrows (e.g. a 422) for the form to render inline, without toasting', async () => {
      const apiError = { status: 422, data: { errors: { title: ['required'] } } }
      mockApi.event.create.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const toastStore = useToastStore()

      await expect(store.createEvent({})).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('updateEvent', () => {
    it('returns the bare id and invalidates the cached current entry', async () => {
      mockApi.event.update.mockResolvedValueOnce({ id: 5 })

      const store = useEventsStore()
      store.current.data = { id: 5, title: 'Stale' }

      const id = await store.updateEvent(5, { title: 'Fresh' })

      expect(mockApi.event.update).toHaveBeenCalledWith(5, { title: 'Fresh' })
      expect(id).toBe(5)
      expect(store.current.data).toBeNull()
    })

    it('rethrows on failure without toasting', async () => {
      const apiError = { status: 422, data: { errors: { location: ['geocode failed'] } } }
      mockApi.event.update.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      const toastStore = useToastStore()

      await expect(store.updateEvent(5, {})).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  // Gap 13: EventAttendanceCount.vue's +/- stepper. Unlike updateEvent
  // above, this patches the cached `current` entry in place rather than
  // invalidating it - a full-page loading-skeleton flash on every +/-
  // click would be a regression from legacy's instant local update.
  describe('updateEventCount', () => {
    it('patches the cached current entry\'s stats field in place, without invalidating it', async () => {
      mockApi.event.update.mockResolvedValueOnce({ id: 5 })

      const store = useEventsStore()
      store.current.data = { id: 5, title: 'Repair Café', stats: { participants: 4, volunteers: 1 } }

      await store.updateEventCount(5, { participants: 7 }, 'participants', 7)

      expect(mockApi.event.update).toHaveBeenCalledWith(5, { participants: 7 })
      expect(store.current.data).toEqual({ id: 5, title: 'Repair Café', stats: { participants: 7, volunteers: 1 } })
    })

    it('does not touch current when it belongs to a different event', async () => {
      mockApi.event.update.mockResolvedValueOnce({ id: 5 })

      const store = useEventsStore()
      store.current.data = { id: 99, stats: { participants: 4 } }

      await store.updateEventCount(5, { participants: 7 }, 'participants', 7)

      expect(store.current.data).toEqual({ id: 99, stats: { participants: 4 } })
    })

    it('rethrows on failure, leaving current untouched', async () => {
      const apiError = { status: 403 }
      mockApi.event.update.mockRejectedValueOnce(apiError)

      const store = useEventsStore()
      store.current.data = { id: 5, stats: { participants: 4 } }

      await expect(store.updateEventCount(5, { participants: 7 }, 'participants', 7)).rejects.toEqual(apiError)
      expect(store.current.data).toEqual({ id: 5, stats: { participants: 4 } })
    })
  })

  describe('uploadEventImage', () => {
    it('returns the response data on success', async () => {
      mockApi.event.uploadImage.mockResolvedValueOnce({ data: { image_url: '/uploads/mid_x.png' } })

      const store = useEventsStore()
      const data = await store.uploadEventImage(5, 'key123')

      expect(mockApi.event.uploadImage).toHaveBeenCalledWith(5, 'key123')
      expect(data).toEqual({ image_url: '/uploads/mid_x.png' })
    })

    it('rethrows on failure (caller shows its own error message)', async () => {
      const apiError = { status: 422 }
      mockApi.event.uploadImage.mockRejectedValueOnce(apiError)

      const store = useEventsStore()

      await expect(store.uploadEventImage(5, 'key123')).rejects.toEqual(apiError)
    })
  })
})
