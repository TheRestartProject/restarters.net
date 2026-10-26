import moment from 'moment'

// Records from future events are almost always events entered with the wrong date, so the Fixometer only shows
// repairs up to today by default.
export function today() {
  return moment().format('YYYY-MM-DD')
}
