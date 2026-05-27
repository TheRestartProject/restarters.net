// Extract a human-readable error message from an Axios (or fetch-style) error.
//
// Axios sets `error.message` to "Request failed with status code 500" by default,
// which isn't useful to a user. The actual reason is usually in the response body:
//   - Laravel validation responses: { message, errors: { field: [msg, ...] } }
//   - Laravel/our API errors: { message: "..." } or { error: "..." }
//   - Plain string bodies on some endpoints
// We prefer the most specific message available and fall back to the generic
// error.message so we still say something even when the server gives us nothing.

export function apiErrorMessage(error) {
  if (!error) return 'Request failed'

  const data = error.response && error.response.data

  if (data && typeof data === 'object') {
    if (data.errors && typeof data.errors === 'object') {
      for (const field of Object.keys(data.errors)) {
        const messages = data.errors[field]
        if (Array.isArray(messages) && messages.length && typeof messages[0] === 'string') {
          return messages[0]
        }
        if (typeof messages === 'string' && messages) {
          return messages
        }
      }
    }
    if (typeof data.message === 'string' && data.message.trim()) {
      return data.message
    }
    if (typeof data.error === 'string' && data.error.trim()) {
      return data.error
    }
  }

  if (typeof data === 'string' && data.trim()) {
    return data
  }

  if (typeof error.message === 'string' && error.message.trim()) {
    return error.message
  }

  return 'Request failed'
}
