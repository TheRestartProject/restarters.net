import { describe, expect, it } from 'vitest'
import { brokenLink, IGNORE_ERRORS, pathShape, sentryOptions } from '~/utils/sentry.js'

const location = new URL('https://restarters.net/group/view/12')

describe('sentryOptions', () => {
  const config = { sentryDsn: 'https://key@o1.ingest.sentry.io/2', sentryEnvironment: 'production' }

  it('is off unless the deployment names an environment', () => {
    expect(sentryOptions({ ...config, sentryEnvironment: '' })).toBeNull()
    expect(sentryOptions({ ...config, sentryDsn: '' })).toBeNull()
    expect(sentryOptions()).toBeNull()
  })

  it('reports to the configured environment, ignoring extension noise', () => {
    const options = sentryOptions(config)
    expect(options.dsn).toBe(config.sentryDsn)
    expect(options.environment).toBe('production')
    expect(options.ignoreErrors).toBe(IGNORE_ERRORS)
  })

  it('only sends events for visitors who accepted analytics cookies', () => {
    let consent = false
    const options = sentryOptions(config, () => consent)
    const event = { message: 'x' }

    expect(options.beforeSend(event)).toBeNull()
    consent = true
    expect(options.beforeSend(event)).toBe(event)
  })

  const frame = (filename) => ({ exception: { values: [{ stacktrace: { frames: [{ filename }] } }] } })

  it('drops errors from code put into the page rather than loaded from a script', () => {
    // The Google app on iOS: frames at lines of the page that hold markup.
    const options = sentryOptions(config, () => true)

    expect(options.beforeSend(frame('https://restarters.net/user/register'))).toBeNull()
    expect(options.beforeSend(frame('https://restarters.net/_nuxt/entry-B5x9Yq2k.js?v=1'))).not.toBeNull()
  })

  it('drops requests that got no response, but not the server turning one down', () => {
    const options = sentryOptions(config, () => true)
    const event = { message: 'x' }
    const failure = (status) => Object.assign(new Error('API request failed'), { name: 'APIError', status })

    expect(options.beforeSend(event, { originalException: failure(null) })).toBeNull()
    expect(options.beforeSend(event, { originalException: failure(500) })).toBe(event)
    expect(options.beforeSend(event, { originalException: new Error('x') })).toBe(event)
  })
})

describe('pathShape', () => {
  it.each([
    ['/group/view/1112', '/group/view/N'],
    ['/group/view/12?tab=events', '/group/view/N'],
    ['/_nuxt/entry-B5x9Yq2k.js', '/_nuxt/entry-HASH.js'],
    ['/16636950505b1291afec1d2c97cb86a90baa231000bfec1ff612055.png', '/HASH.png'],
    ['/fixometer', '/fixometer'],
  ])('%s -> %s', (path, shape) => {
    expect(pathShape(path)).toBe(shape)
  })
})

describe('brokenLink', () => {
  it('reports an in-app link to a missing page', () => {
    expect(brokenLink({ path: '/group/view/undefined', previousPath: '/group/view/982', referrer: '', location }))
      .toEqual(['restarters.net', '/group/view/undefined'])
  })

  it('reports a link from another of our sites', () => {
    expect(brokenLink({ path: '/old-page', previousPath: null, referrer: 'https://wiki.restarters.net/Main_Page', location }))
      .toEqual(['wiki.restarters.net', '/old-page'])
    expect(brokenLink({ path: '/group/view/5', previousPath: null, referrer: 'https://therestartproject.org/', location }))
      .toEqual(['therestartproject.org', '/group/view/N'])
  })

  it.each([
    ['no referrer: a crawler or bookmark', ''],
    ['someone else\'s site', 'https://example.com/'],
    ['a faked http referrer', 'http://restarters.net/'],
    ['a look-alike host', 'https://notrestarters.net/'],
    ['the page itself: a reload', 'https://restarters.net/old-page'],
  ])('ignores %s', (_, referrer) => {
    expect(brokenLink({ path: '/old-page', previousPath: null, referrer, location })).toBeNull()
  })

  it('ignores a navigation to the same route', () => {
    expect(brokenLink({ path: '/old-page', previousPath: '/old-page', referrer: '', location })).toBeNull()
  })
})
