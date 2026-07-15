/**
 * Thrown by BaseAPI when a request fails. Carries enough of the failed
 * request/response for callers to branch on status (e.g. 422 validation
 * errors, 401 unauthenticated) without re-parsing the ofetch error shape.
 */
export default class APIError extends Error {
  constructor({ status, data, path, method }) {
    const message =
      (data && (data.message || data.error)) ||
      `API request failed: ${method} ${path} (${status})`

    super(message)

    this.name = 'APIError'
    this.status = status
    this.data = data
    this.path = path
    this.method = method
  }
}
