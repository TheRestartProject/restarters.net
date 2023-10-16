import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Array indexed by group id containing array of volunteers.
    byGroup: {},
    listGroup: {}
  },
  getters: {
    byGroup: state => groupID => {
      return state.byGroup[groupID]
    },
    byIDGroup: state => (id) => {
        return state.listGroup[id]
    }
  },
  mutations: {
    setGroup(state, params) {
      Vue.set(state.byGroup, params.groupID, params.volunteers)

      params.volunteers.forEach(volunteer => {
        Vue.set(state.listGroup, volunteer.id, volunteer)
      })
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    async fetchGroup({commit}, id) {
      const ret = await axios.get('/api/v2/groups/' + id + '/volunteers')
      commit('setGroup', {
        groupID: id,
        volunteers: ret.data.data
      })
    }
  },
}