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
      let list = {}
      params.groups.forEach(e => {
        list[e.idgroups] = e
      })

      state.list = list
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
    },
    async unfollow({commit, rootGetters, getters}, params) {
      // We can't use the DELETE verb because we want to pass the api token as a parameter so that it doesn't
      // show up in the URL in logs, which is bad practice.  So use POST with _method to override.
      const apiToken = rootGetters['auth/apiToken']

      const ret = await axios.post('/api/usersgroups/' + params.idgroups, {
        _method: 'delete',
        api_token: apiToken
      })

      if (ret.data.success) {
        // TODO LATER We partially upgrade the group here.  It would be better to have a proper API call to get the
        // group, and update the whole thing.
        const group = getters.get(params.idgroups)
        group.all_restarters_count = ret.data.all_restarters_count
        group.all_hosts_count = ret.data.all_hosts_count
        group.ingroup = false
        commit('set', group)
      }
    }
  },
}