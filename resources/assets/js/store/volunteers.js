import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Array indexed by group id containing array of volunteers.
    list: []
  },
  getters: {
    byEvent: state => groupID => {
      return state.list[groupID]
    }
  },
  mutations: {
    set(state, params) {
      Vue.set(state.list, params.groupID, params.volunteers)
    },
    remove(state, params) {
      let newarr = state.list[params.groupID].filter((a) => {
        return a.user !== params.userId
      })

      Vue.set(state.list, params.groupID, newarr)
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    }
  },
}