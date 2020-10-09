import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Array indexed by event id containing array of devices.
    list: []
  },
  getters: {
    byEvent: state => idevents => {
      return state.list[idevents]
    }
  },
  mutations: {
    set(state, params) {
      Vue.set(state.list, params.idevents, params.devices)
    },
    add(state, params) {
      // Add the new device to the existing list.
      state.list[params.idevents].push(params)
    },
    remove(state, params) {
      let newarr = state.list[params.idevents].filter((a) => {
        return a.iddevices !== params.iddevices
      })

      Vue.set(state.list, params.idevents, newarr)
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
    async add({commit}, params) {
      let ret = await axios.post('/device/create', params, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      if (ret && ret.data && ret.data.success && ret.data.devices) {
        // We have been returned the device objects from the server.  Add them into the store, and lo!  All our
        // stats and views will update.
        commit('add', ret.data.devices[0]);
      }
    },
    async delete({commit}, params) {
      const ret = await axios.get('/device/delete/' + params.iddevices, {
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