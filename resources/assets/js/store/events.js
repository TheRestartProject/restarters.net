import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // List of events indexed by event id.  Use object rather than array so that it's sparse.
    list: {},

    // List of stats indexed by event id.
    stats: {}
  },
  getters: {
    get: state => idevents => {
      return state.list[idevents]
    },
    getByGroup: state => idgroups => {
      return Object.values(state.list).filter(e => e.group === idgroups)
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
    async delete({commit}, params) {
      let ret = await axios.post('/party/delete/' + params.idevents, {
        headers: {
          // TODO LATER We shouldn't be pulling the token using jQuery.
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      commit('remove', params)
    }
  },
}