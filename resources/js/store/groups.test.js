// Tests in-flight de-duplication of the groups/fetch action.
//
// The action is called eagerly by GroupsPage (one dispatch per yourGroups id)
// and again whenever a user navigates to a group page. Without de-dup, the
// same id can be in flight twice concurrently.

jest.mock('axios', () => ({
  __esModule: true,
  default: {
    get: jest.fn(),
    post: jest.fn(),
    patch: jest.fn(),
    delete: jest.fn(),
  },
}))
import axios from 'axios'

import groups from './groups'

// store/groups.js reads locale via document.getElementById('language-current').innerText.
// jsdom doesn't populate innerText reliably, so set the property explicitly.
beforeEach(() => {
  document.body.innerHTML = '<div id="language-current"></div>'
  const el = document.getElementById('language-current')
  Object.defineProperty(el, 'innerText', { value: 'en', configurable: true })
  axios.get.mockReset()
})

function commit() {}
const rootGetters = { 'auth/apiToken': 'TEST' }

function deferred() {
  let resolve, reject
  const promise = new Promise((res, rej) => { resolve = res; reject = rej })
  return { promise, resolve, reject }
}

test('two concurrent fetches for the same group share a single in-flight request', async () => {
  const d = deferred()
  axios.get.mockReturnValueOnce(d.promise)

  const a = groups.actions.fetch({ rootGetters, commit }, { id: 42 })
  const b = groups.actions.fetch({ rootGetters, commit }, { id: 42 })

  expect(axios.get).toHaveBeenCalledTimes(1)

  d.resolve({ data: { data: { id: 42, name: 'G' } } })
  await Promise.all([a, b])

  expect(axios.get).toHaveBeenCalledTimes(1)
})

test('a new fetch after the previous one settled hits the network again', async () => {
  axios.get.mockResolvedValueOnce({ data: { data: { id: 7, name: 'G7' } } })
  await groups.actions.fetch({ rootGetters, commit }, { id: 7 })
  expect(axios.get).toHaveBeenCalledTimes(1)

  axios.get.mockResolvedValueOnce({ data: { data: { id: 7, name: 'G7 again' } } })
  await groups.actions.fetch({ rootGetters, commit }, { id: 7 })
  expect(axios.get).toHaveBeenCalledTimes(2)
})

test('concurrent fetches for different groups each get their own request', async () => {
  const d1 = deferred()
  const d2 = deferred()
  axios.get.mockReturnValueOnce(d1.promise).mockReturnValueOnce(d2.promise)

  const a = groups.actions.fetch({ rootGetters, commit }, { id: 1 })
  const b = groups.actions.fetch({ rootGetters, commit }, { id: 2 })

  expect(axios.get).toHaveBeenCalledTimes(2)

  d1.resolve({ data: { data: { id: 1 } } })
  d2.resolve({ data: { data: { id: 2 } } })
  await Promise.all([a, b])
})

test('a fetch that throws clears its in-flight slot so retries can run', async () => {
  axios.get.mockRejectedValueOnce(new Error('boom'))
  await groups.actions.fetch({ rootGetters, commit }, { id: 9 })

  // The previous fetch is no longer in flight, so a new one re-hits the network.
  axios.get.mockResolvedValueOnce({ data: { data: { id: 9 } } })
  await groups.actions.fetch({ rootGetters, commit }, { id: 9 })

  expect(axios.get).toHaveBeenCalledTimes(2)
})

test('fetches with the same id but different includeStats are independent requests', async () => {
  axios.get.mockResolvedValue({ data: { data: { id: 3 } } })

  await groups.actions.fetch({ rootGetters, commit }, { id: 3, includeStats: false })
  await groups.actions.fetch({ rootGetters, commit }, { id: 3, includeStats: true })

  expect(axios.get).toHaveBeenCalledTimes(2)
  expect(axios.get.mock.calls[0][0]).toContain('includeStats=false')
  expect(axios.get.mock.calls[1][0]).toContain('includeStats=true')
})

test('the details list fetch asks for archived groups (shown with a badge, as the old page did)', async () => {
  axios.get.mockResolvedValueOnce({ data: { data: [] } })

  await groups.actions.list({ commit }, { details: true })

  expect(axios.get.mock.calls[0][0]).toContain('archived=true')
})

// GroupsTable renders its rows from the groups/list store; groups that only
// exist in the moderate store never showed up, so the "groups requiring
// moderation" section rendered an empty table.
describe('getModerationRequired', () => {
  const Vue = require('vue')
  const Vuex = require('vuex')
  Vue.use(Vuex)

  function makeRealStore() {
    return new Vuex.Store({
      modules: {
        groups: {
          ...groups,
          state: { list: {}, moderate: {}, tags: {}, stats: {} },
        },
        auth: {
          namespaced: true,
          getters: { apiToken: () => 'TEST' },
        },
      },
    })
  }

  test('moderation groups become renderable by GroupsTable (in the list store, with an id)', async () => {
    axios.get.mockResolvedValueOnce({
      data: [{ id: 7, name: 'Mod Group', location: 'Somewhere', country: 'UK', networks: [] }],
    })
    const store = makeRealStore()

    await store.dispatch('groups/getModerationRequired')

    expect(store.getters['groups/getModerate'][7]).toBeTruthy()
    const listed = store.getters['groups/list'].find(g => g.id === 7)
    expect(listed).toBeTruthy()
    expect(listed.idgroups).toBe(7)
  })

  test('does not clobber a richer entry already in the list store', async () => {
    const store = makeRealStore()
    store.commit('groups/set', { id: 7, name: 'Rich', location: { lat: 1, lng: 2 } })
    axios.get.mockResolvedValueOnce({
      data: [{ id: 7, name: 'Mod Group', networks: [] }],
    })

    await store.dispatch('groups/getModerationRequired')

    expect(store.getters['groups/get'](7).location).toEqual({ lat: 1, lng: 2 })
  })
})
