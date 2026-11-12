import EventAddVolunteerModal from './EventAddVolunteerModal.vue'

test('has no group volunteers until they have been fetched', () => {
  // The options list threw "this.groupVolunteers is undefined" while the fetch was in flight.
  const ctx = { groupId: 5, $store: { getters: { 'volunteers/byGroup': () => undefined } } }

  expect(EventAddVolunteerModal.computed.groupVolunteers.call(ctx)).toEqual([])
})

test('lists the group volunteers once fetched', () => {
  const volunteers = [{ user: 1, name: 'Jo' }]
  const ctx = { groupId: 5, $store: { getters: { 'volunteers/byGroup': () => volunteers } } }

  expect(EventAddVolunteerModal.computed.groupVolunteers.call(ctx)).toEqual(volunteers)
})
