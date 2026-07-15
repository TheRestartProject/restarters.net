import { describe, expect, it, vi } from 'vitest'

const ofetchMock = vi.fn()
vi.mock('ofetch', () => ({
  ofetch: (...args) => ofetchMock(...args),
}))

const { default: BaseAPI } = await import('../../app/api/BaseAPI.js')
const { default: APIError } = await import('../../app/api/APIError.js')

describe('BaseAPI', () => {
  it('injects the Authorization header when a token is present', async () => {
    ofetchMock.mockResolvedValueOnce({ data: 'ok' })

    const api = new BaseAPI({
      base: 'http://localhost:8001',
      getToken: () => 'abc123',
      getLocale: () => null,
    })

    await api.$get('/api/v2/session')

    expect(ofetchMock).toHaveBeenCalledWith(
      '/api/v2/session',
      expect.objectContaining({
        headers: { Authorization: 'Bearer abc123' },
      })
    )
  })

  it('omits the Authorization header when there is no token', async () => {
    ofetchMock.mockResolvedValueOnce({ data: 'ok' })

    const api = new BaseAPI({
      base: 'http://localhost:8001',
      getToken: () => null,
      getLocale: () => null,
    })

    await api.$get('/api/v2/session')

    expect(ofetchMock).toHaveBeenCalledWith(
      '/api/v2/session',
      expect.objectContaining({ headers: {} })
    )
  })

  it('injects the locale as a query param on every call', async () => {
    ofetchMock.mockResolvedValueOnce({ data: 'ok' })

    const api = new BaseAPI({
      base: 'http://localhost:8001',
      getToken: () => null,
      getLocale: () => 'fr',
    })

    await api.$get('/api/v2/groups', { page: 1 })

    expect(ofetchMock).toHaveBeenCalledWith(
      '/api/v2/groups',
      expect.objectContaining({
        query: { page: 1, locale: 'fr' },
      })
    )
  })

  it('throws an APIError with status/data/path/method on failure', async () => {
    ofetchMock.mockRejectedValueOnce({
      response: { status: 422, _data: { message: 'Bad', errors: {} } },
    })

    const api = new BaseAPI({ base: 'http://localhost:8001' })

    await expect(api.$post('/api/v2/auth/login', {})).rejects.toMatchObject({
      status: 422,
      data: { message: 'Bad', errors: {} },
      path: '/api/v2/auth/login',
      method: 'POST',
    })

    ofetchMock.mockRejectedValueOnce({
      response: { status: 422, _data: { message: 'Bad', errors: {} } },
    })
    await expect(api.$post('/api/v2/auth/login', {})).rejects.toBeInstanceOf(
      APIError
    )
  })

  it('calls onUnauthorized only when a 401 comes from /session', async () => {
    const onUnauthorized = vi.fn()
    const api = new BaseAPI({ base: 'http://localhost:8001', onUnauthorized })

    ofetchMock.mockRejectedValueOnce({ response: { status: 401, _data: {} } })
    await expect(api.$get('/api/v2/session')).rejects.toBeInstanceOf(APIError)
    expect(onUnauthorized).toHaveBeenCalledTimes(1)

    onUnauthorized.mockClear()

    ofetchMock.mockRejectedValueOnce({ response: { status: 401, _data: {} } })
    await expect(api.$get('/api/v2/groups/1')).rejects.toBeInstanceOf(
      APIError
    )
    expect(onUnauthorized).not.toHaveBeenCalled()
  })
})
