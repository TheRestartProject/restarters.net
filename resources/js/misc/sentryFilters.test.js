import { thrownByAScript, scrubEvent, scrubBreadcrumb } from './sentryFilters'

const withFrames = (...filenames) => ({ exception: { values: [{ stacktrace: { frames: filenames.map(filename => ({ filename })) } }] } })

describe('thrownByAScript', () => {
  test('keeps errors with a frame in one of our scripts', () => {
    expect(thrownByAScript(withFrames('<anonymous>', 'https://restarters.net/build/assets/app-d07ea527.js'))).toBe(true)
    expect(thrownByAScript(withFrames('https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js?v=1'))).toBe(true)
  })

  test('drops errors whose only frames are the page itself', () => {
    // The Google app on iOS: "Maximum call stack size exceeded" at lines of /user/register that hold markup.
    expect(thrownByAScript(withFrames('https://restarters.net/user/register', 'https://restarters.net/user/register'))).toBe(false)
  })

  test('keeps messages and errors without a stack', () => {
    expect(thrownByAScript({ message: 'Missing translation' })).toBe(true)
  })
})

describe('scrubbing the API token', () => {
  const token = 'T4mcqGtBPJCkF00YjYAb99nvTby7dSspLaSIZ7jRykqNwWJJh8nOr3Zk86on'

  test('from requests, spans, breadcrumbs and extra data', () => {
    const event = scrubEvent({
      request: {
        url: `https://restarters.net/group?api_token=${token}`,
        headers: { Referer: `https://restarters.net/party?x=1&api_token=${token}` },
      },
      spans: [{ description: `GET /api/v2/groups/926?api_token=${token}&locale=en`, data: { url: `/api/v2/groups/926?api_token=${token}` } }],
      breadcrumbs: [{ category: 'navigation', data: { from: `/a?api_token=${token}`, to: '/b' } }],
      extra: { url: `/c?api_token=${token}` },
    })

    expect(JSON.stringify(event)).not.toContain(token)
    expect(event.spans[0].description).toBe('GET /api/v2/groups/926?api_token=[Filtered]&locale=en')
  })

  test('from breadcrumbs as they are recorded', () => {
    const crumb = scrubBreadcrumb({ category: 'xhr', data: { method: 'GET', url: `/api/v2/alerts?api_token=${token}`, status_code: 200 } })

    expect(crumb.data.url).toBe('/api/v2/alerts?api_token=[Filtered]')
    expect(crumb.data.status_code).toBe(200)
  })
})
