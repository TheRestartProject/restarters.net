import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // List of networks
    list: {},
  },
  getters: {
    list: state => {
      return Object.values(state.list)
    },
  },
  mutations: {
    setList(state, params) {
      let list = {}
      params.groups.forEach(e => {
        list[e.idgroups || e.id] = e
      })

      state.list = list
    },
  },
  actions: {
    async list({commit}) {
      let ret = await axios.get('/api/v2/networks')
      if (ret && ret.data) {
        commit('setList', {
          groups: ret.data.data
        })
      }
    },
  },
}