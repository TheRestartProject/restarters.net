import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // List of groups indexed by group id.  Use object rather than array so that it's sparse.
    list: {},

    // List of stats indexed by group id.
    stats: {}
  },
  getters: {
    get: state => idgroups => {
      return state.list[idgroups]
    },
    list: state => {
      return Object.values(state.list)
    },
    getStats: state => idgroups => {
      return state.stats[idgroups]
    }
  },
  mutations: {
    set(state, params) {
      Vue.set(state.list, params.idgroups, params)
    },
    setList(state, params) {
      params.groups.forEach(e => {
        Vue.set(state.list, e.idgroups, e)
      })
    },
    setStats(state, params) {
      Vue.set(state.stats, params.idgroups, params.stats)
    },
    remove(state, params) {
      delete state.list[params.idgroups]
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
    }
  },
}