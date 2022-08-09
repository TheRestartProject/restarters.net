import Vue from 'vue'

const axios = require('axios')

function newToOld(e) {
  // We are in the frustrating position of having a half-written new API with sensible field names, but existing
  // Vue components that expect old-style field names.  We therefore sometimes need to convert the new API data
  // back into the old format which is expected.  In some bright future where we have shifted over to using the
  // new API completely, we can then migrate the Vue components to use the new field names and retire this function.
  // Similar code in group store.
  let ret = {
    idevents: e.id,
    title: e.title,
    location: e.location,
    group: {
      idgroups: e.group.id,
      name: e.group.name
    },
    volunteers: e.stats.volunteers,
    allinvitedcount: e.stats.invited
  }

  return ret
}

export default {
  namespaced: true,
  state: {
    // List of events indexed by event id.  Use object rather than array so that it's sparse.
    list: {},
    moderate: {},

    // List of stats indexed by event id.
    stats: {}
  },
  getters: {
    get: state => idevents => {
      return state.list[idevents]
    },
    getAll: state => state.list,
    getModerate: state => state.moderate,
    getByGroup: state => idgroups => {
      // null idgroups means fetch all
      return Object.values(state.list).filter(e => (idgroups === null || e.group === idgroups))
    },
    getStats: state => idevents => {
      return state.stats[idevents]
    }
  },
  mutations: {
    set(state, params) {
      // There is a separate store for devices.  Make sure we don't accidentally use the list of devices returned
      // on the object, because that isn't updated dynamically.
      params.devices = null
      Vue.set(state.list, params.idevents, params)
    },
    setList(state, params) {
      params.events.forEach(e => {
        Vue.set(state.list, e.idevents, e)
      })
    },
    setModerate(state, params) {
      params.forEach(e => {
        Vue.set(state.moderate, e.id, newToOld(e))
      })
    },
    setStats(state, params) {
      Vue.set(state.stats, params.idevents, params.stats)
    },
    remove(state, params) {
      delete state.list[params.idevents]
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    setList({commit}, params) {
      commit('setList', params);
    },
    setStats({commit}, params) {
      commit('setStats', params);
    },
    async delete({commit, rootGetters}, params) {
      let ret = await axios.post('/party/delete/' + params.idevents, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      commit('remove', params)
    },
    async getModerationRequired({commit, rootGetters}, params) {
      const apiToken = rootGetters['auth/apiToken']

      let ret = await axios.get('/api/v2/moderate/events?api_token=' + apiToken)

      if (ret && ret.data) {
        commit('setModerate', ret.data)
      }
    }
  },
}