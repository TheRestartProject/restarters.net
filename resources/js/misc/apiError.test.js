import { apiErrorMessage } from './apiError'

describe('apiErrorMessage', () => {
  test('returns Laravel validation field message when present', () => {
    const error = {
      message: 'Request failed with status code 422',
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            website: ['The website must be a valid URL.'],
          },
        },
      },
    }
    expect(apiErrorMessage(error)).toBe('The website must be a valid URL.')
  })

  test('returns first field message when multiple validation errors', () => {
    const error = {
      response: {
        data: {
          errors: {
            name: ['Name is required.'],
            email: ['Email is not valid.'],
          },
        },
      },
    }
    const msg = apiErrorMessage(error)
    expect(msg === 'Name is required.' || msg === 'Email is not valid.').toBe(true)
  })

  test('returns response.data.message for non-validation API errors', () => {
    const error = {
      message: 'Request failed with status code 500',
      response: {
        status: 500,
        data: { message: 'Could not contact the geocoder.' },
      },
    }
    expect(apiErrorMessage(error)).toBe('Could not contact the geocoder.')
  })

  test('returns response.data.error when message field absent', () => {
    const error = {
      message: 'Request failed with status code 400',
      response: { data: { error: 'Group name already taken.' } },
    }
    expect(apiErrorMessage(error)).toBe('Group name already taken.')
  })

  test('returns plain string response body when present', () => {
    const error = {
      message: 'Request failed with status code 500',
      response: { data: 'Internal Server Error' },
    }
    expect(apiErrorMessage(error)).toBe('Internal Server Error')
  })

  test('falls back to error.message when no response body', () => {
    const error = { message: 'Network Error' }
    expect(apiErrorMessage(error)).toBe('Network Error')
  })

  test('returns generic fallback for null/undefined error', () => {
    expect(apiErrorMessage(null)).toBe('Request failed')
    expect(apiErrorMessage(undefined)).toBe('Request failed')
  })

  test('prefers field error over top-level message', () => {
    const error = {
      response: {
        data: {
          message: 'The given data was invalid.',
          errors: { website: ['The website field must be a URL.'] },
        },
      },
    }
    expect(apiErrorMessage(error)).toBe('The website field must be a URL.')
  })

  test('ignores empty/whitespace fields and tries the next one', () => {
    const error = {
      message: 'Request failed',
      response: { data: { message: '   ', error: 'real error' } },
    }
    expect(apiErrorMessage(error)).toBe('real error')
  })

  test('handles validation errors object where value is a single string', () => {
    const error = {
      response: {
        data: { errors: { name: 'Name already taken' } },
      },
    }
    expect(apiErrorMessage(error)).toBe('Name already taken')
  })
})
