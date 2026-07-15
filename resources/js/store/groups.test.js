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

  expect(axios.get.mock.calls[0][0]).toContain('includeArchived=true')
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

describe('hydrate', () => {
  test('fetches full rows for un-hydrated ids in one batched call', async () => {
    axios.get.mockResolvedValueOnce({ data: { data: [{ id: 1, summary: true }, { id: 2, summary: true }] } })
    const commits = []
    const state = { list: {}, hydrating: {} }

    await groups.actions.hydrate(
      { commit: (type, params) => commits.push([type, params]), state },
      { ids: [1, 2] }
    )

    expect(axios.get).toHaveBeenCalledTimes(1)
    const url = axios.get.mock.calls[0][0]
    expect(url).toContain('/api/v2/groups/summary')
    expect(url).toContain('ids=1,2')
    expect(url).toContain('includeNextEvent=true')
    expect(url).toContain('includeCounts=true')
    expect(commits.filter(c => c[0] === 'set').map(c => c[1].id)).toEqual([1, 2])
  })

  test('skips ids already hydrated or in flight', async () => {
    const state = {
      // A full row has summary: true; a bare index entry does not.
      list: { 1: { id: 1, summary: true }, 2: { id: 2 } },
      hydrating: { 2: true },
    }

    await groups.actions.hydrate({ commit: () => {}, state }, { ids: [1, 2] })

    expect(axios.get).not.toHaveBeenCalled()
  })

  test('chunks requests at the API cap of 200 ids', async () => {
    axios.get.mockResolvedValue({ data: { data: [] } })
    const state = { list: {}, hydrating: {} }
    const ids = Array.from({ length: 250 }, (_, i) => i + 1)

    await groups.actions.hydrate({ commit: () => {}, state }, { ids })

    expect(axios.get).toHaveBeenCalledTimes(2)
    expect(axios.get.mock.calls[0][0]).toContain('ids=1,')
    expect(axios.get.mock.calls[1][0]).toContain('ids=201,')
  })
})

test('the index fetch shapes entries for the map, filters and table', async () => {
  axios.get.mockResolvedValueOnce({
    data: { data: [{ id: 7, name: 'G', lat: 51, lng: 0, country: 'United Kingdom', network_ids: [3], tag_ids: [9], archived_at: null }] }
  })
  const commits = []

  await groups.actions.list(
    { commit: (type, params) => commits.push([type, params]) },
    { details: true }
  )

  expect(axios.get.mock.calls[0][0]).toContain('/api/v2/groups/names')
  const g = commits.find(c => c[0] === 'setList')[1].groups[0]
  expect(g.location).toEqual({ location: null, country: 'United Kingdom', lat: 51, lng: 0 })
  expect(g.networks).toEqual([3])
  expect(g.group_tags_full).toEqual([{ id: 9 }])
})
