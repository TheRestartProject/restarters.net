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
    remove(state, params) {
      let newarr = state.list[params.idevents].filter((a) => {
        return a.user !== params.userId
      })

      Vue.set(state.list, params.idevents, newarr)
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    async remove({commit}, params) {
      let ret = await axios.post('/party/remove-volunteer', {
        user_id: params.userId,
        event_id: params.idevents
      }, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      if (ret && ret.data && ret.data.success) {
        commit('remove', params)
      } else {
        throw new Exception("Server request failed")
      }
    }
  },
}