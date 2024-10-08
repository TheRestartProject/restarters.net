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
    removeVolunteer(state, id) {
      const vol = state.listGroup[id]
      Vue.delete(state.listGroup, id)

      const vols = state.byGroup[vol.group].filter(v => v.id !== id)
      Vue.set(state.byGroup, vol.group, vols)
    },
    makeHost(state, id) {
      state.listGroup[id].host = true
    },
    removeHost(state, id) {
      state.listGroup[id].host = false
    }
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
    },
    async remove({commit, dispatch}, id) {
      const vol = this.state.volunteers.listGroup[id]
      const ret = await axios.delete('/api/v2/groups/' + vol.group + '/volunteers/' + vol.user)
      commit('removeVolunteer', id)
    },
    async makehost({commit, dispatch}, id) {
      const vol = this.state.volunteers.listGroup[id]
      const ret = await axios.patch('/api/v2/groups/' + vol.group + '/volunteers/' + vol.user, {
        host: true
      })
      commit('makeHost', id)
    },
    async removehost({commit, dispatch}, id) {
      const vol = this.state.volunteers.listGroup[id]
      const ret = await axios.patch('/api/v2/groups/' + vol.group + '/volunteers/' + vol.user, {
        host: false
      })
      commit('removeHost', id)
    }
  },
}