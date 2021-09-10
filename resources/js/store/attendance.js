import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Array indexed by event id containing array of attendees.
    list: []
  },
  getters: {
    byEvent: state => idevents => {
      return state.list[idevents]
    }
  },
  mutations: {
    set(state, params) {
      Vue.set(state.list, params.idevents, params.attendees)
    },
    remove(state, id) {
      // The id we are passed is the id in events_users, but the store is indexed by event id.  So we need to
      // iterate through to find the one to remove.  This is rare and the numbers involved aren't huge, so the
      // performance is ok.
      state.list.forEach((list, idevents) => {
        let newarr = state.list[idevents].filter((a) => {
          return a.idevents_users !== id
        })

        Vue.set(state.list, idevents, newarr)
      })
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    async remove({commit, rootGetters}, params) {
      let ret = await axios.post('/party/remove-volunteer', {
        id: params.id
      }, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success) {
        commit('remove', params.id)
      } else {
        throw new Exception("Server request failed")
      }
    }
  },
}