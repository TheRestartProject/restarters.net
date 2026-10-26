// The events/edit action sends every field the form gives it, blanks included, because the API only changes the
// fields it is sent.

jest.mock('axios', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
    post: jest.fn(),
  },
}))
import axios from 'axios'

import events from './events'

const rootGetters = { 'auth/apiToken': 'TEST' }

test('edit sends blanks and booleans so they can be cleared', async () => {
  axios.post.mockResolvedValueOnce({ data: { id: 5 } })

  const id = await events.actions.edit({ rootGetters, commit() {} }, {
    id: 5,
    title: 'Library',
    link: null,
    location: '',
    online: false,
    timezone: undefined,
  })

  expect(id).toBe(5)
  const formData = axios.post.mock.calls[0][1]
  expect(formData.get('title')).toBe('Library')
  expect(formData.get('link')).toBe('')
  expect(formData.get('location')).toBe('')
  expect(formData.get('online')).toBe('0')
  expect(formData.get('timezone')).toBe('')
  expect(formData.get('_method')).toBe('PATCH')
})
