// What the old pages send to Sentry: errors from our own scripts, without the user's API token.

// Errors thrown by code that isn't in any script file - code that the browser or an app (the Google app on iOS does
// this) has put into the page - are reported with the page itself as the file, at lines that hold markup. There is
// nothing of ours to fix in them.
export function thrownByAScript (event) {
  const frames = []

  ;((event.exception && event.exception.values) || []).forEach(v => {
    frames.push(...((v.stacktrace && v.stacktrace.frames) || []))
  })

  return !frames.length || frames.some(f => /\.js$/.test((f.filename || '').split(/[?#]/)[0]))
}

// The old pages put the user's API token in request URLs; it shouldn't be sent to Sentry with them.
export function scrubUrl (url) {
  return typeof url === 'string' ? url.replace(/(api_token=)[^&#\s]*/g, '$1[Filtered]') : url
}

// Every string value of an object (headers, breadcrumb data such as a navigation's from and to).
function scrubValues (obj) {
  if (obj && typeof obj === 'object') {
    Object.keys(obj).forEach(k => {
      obj[k] = scrubUrl(obj[k])
    })
  }
}

export function scrubBreadcrumb (breadcrumb) {
  if (breadcrumb) {
    breadcrumb.message = scrubUrl(breadcrumb.message)
    scrubValues(breadcrumb.data)
  }

  return breadcrumb
}

export function scrubEvent (event) {
  if (event.request) {
    event.request.url = scrubUrl(event.request.url)
    // The Referer is the page before, whose URL may have had the token in it.
    scrubValues(event.request.headers)

    if (typeof event.request.query_string === 'string') {
      event.request.query_string = scrubUrl(event.request.query_string)
    } else {
      scrubValues(event.request.query_string)
    }
  }

  ;(event.spans || []).forEach(span => {
    span.description = scrubUrl(span.description)
    scrubValues(span.data)
  })

  ;(event.breadcrumbs || []).forEach(scrubBreadcrumb)
  scrubValues(event.extra)

  return event
}
