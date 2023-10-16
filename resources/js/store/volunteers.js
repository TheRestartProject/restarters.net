import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Array indexed by group id containing array of volunteers.
    byGroup: []
  },
  getters: {
    byGroup: state => groupID => {
      return state.byGroup[groupID]
    }
  },
  mutations: {
    setGroup(state, params) {
      Vue.set(state.byGroup, params.groupID, params.volunteers)
    },
    removeFromGroup(state, params) {
      let newarr = state.byGroup[params.groupID].filter((a) => {
        return a.user !== params.userId
      })

      Vue.set(state.list, params.groupID, newarr)
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    async fetchGroup({commit}, id) {
      const ret = await axios.get('/api/v2/' + id + '/volunteers')
      console.log('fetche', ret)
      commit('set', {
        groupID: id,
        volunteers: response.data
      })
    }
  },
}