import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Object indexed by event id containing array of devices.  Use object rather than array so that it's sparse.
    list: {}
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
    async add({commit, dispatch}, params) {
      let ret = await axios.post('/device/create', params, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      if (ret && ret.data && ret.data.success && ret.data.devices) {
        // We have been returned the device objects from the server.  Add them into the store, and lo!  All our
        // stats and views will update.
        commit('add', ret.data.devices[0]);

        // Update our stats
        // TODO LATER There are some uses of event_id in the server which should really be idevents for
        // consistency.
        dispatch('events/setStats', {
          idevents: params.event_id,
          stats: ret.data.stats
        }, {
          root: true
        })
      }
    },
    async delete({commit, dispatch}, params) {
      const ret = await axios.get('/device/delete/' + params.iddevices, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      if (ret && ret.data && ret.data.success) {
        commit('remove', params)

        // Update our stats
        dispatch('events/setStats', {
          idevents: params.idevents,
          stats: ret.data.stats
        }, {
          root: true
        })
      } else {
        throw new Exception("Server request failed")
      }
    }
  },
}