import Vue from 'vue'
import axios from 'axios'

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
    async add({ commit, dispatch, rootGetters }, params) {
      const apiToken = rootGetters['auth/apiToken']

      await axios.put('/api/events/' + params.idevents + '/volunteers', {
        api_token: apiToken,
        user: params.user,
        full_name: params.full_name,
        volunteer_email_address: params.volunteer_email_address
      })

      await dispatch('attendance/fetch', {
        idevents: params.idevents
      })
    },
    async fetch({ commit, rootGetters }, params) {
      const apiToken = rootGetters['auth/apiToken']

      let ret = await axios.get('/api/events/' + params.idevents + '/volunteers', {
        params: {
          api_token: apiToken
        }
      })

      if (ret && ret.data && ret.data.success) {
        commit('set', {
          idevents: params.idevents,
          attendees: ret.data.volunteers
        })
      } else {
        throw "Server request failed"
      }
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
        throw "Server request failed"
      }
    }
  },
}