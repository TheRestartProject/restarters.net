import moment from 'moment'
import FixometerPage from './FixometerPage.vue'

jest.mock('./FixometerHeading.vue', () => ({ name: 'FixometerHeading', template: '<div />' }))
jest.mock('./FixometerGlobalImpact.vue', () => ({ name: 'FixometerGlobalImpact', template: '<div />' }))
jest.mock('./FixometerRecordsTable.vue', () => ({ name: 'FixometerRecordsTable', template: '<div />' }))
jest.mock('./FixometerFilters.vue', () => ({ name: 'FixometerFilters', template: '<div />' }))
jest.mock('./AlertBanner.vue', () => ({ name: 'AlertBanner', template: '<div />' }))

const todayStr = moment().format('YYYY-MM-DD')

// Evaluate the computed properties against a plain state object, without mounting the page.
function page(state = {}) {
  const ctx = {
    powered: null, category: null, brand: null, model: null, item_type: null,
    comments: null, wiki: false, status: null, group: null, from_date: null,
    to_date: todayStr, ...state,
  }
  const c = FixometerPage.computed

  Object.defineProperty(ctx, 'filterParams', { get: () => c.filterParams.call(ctx) })
  Object.defineProperty(ctx, 'hasFilters', { get: () => c.hasFilters.call(ctx) })
  Object.defineProperty(ctx, 'filteredDownloadUrl', { get: () => c.filteredDownloadUrl.call(ctx) })

  return ctx
}

function query(p) {
  return new URLSearchParams(p.filteredDownloadUrl.split('?')[1])
}

test('no filters means no filtered download', () => {
  expect(page().hasFilters).toBe(false)
})

test('unpowered only counts as a filter and is sent', () => {
  const p = page({ powered: false })

  expect(p.hasFilters).toBe(true)
  expect(query(p).get('powered')).toBe('false')
})

test('powered is sent as true', () => {
  expect(query(page({ powered: true })).get('powered')).toBe('true')
})

test('the download always includes the to date, even when it is today', () => {
  const p = page({ brand: 'Acme' })

  expect(p.filteredDownloadUrl.startsWith('/export/devices?')).toBe(true)
  expect(query(p).get('brand')).toBe('Acme')
  expect(query(p).get('to_date')).toBe(todayStr)
})

test('a changed to date is a filter', () => {
  expect(page({ to_date: '2020-01-01' }).hasFilters).toBe(true)
})
