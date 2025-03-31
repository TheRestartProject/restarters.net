import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Object indexed by event id containing array of device ids.  Use object rather than array so that it's sparse.
    devicesByEvent: {},

    // Object indexed by device id.
    devicesById: {},
  },
  getters: {
    byId: state => (id) => {
      return state.devicesById[id]
    },
    byEvent: state => eventid => {
      return state.devicesByEvent[eventid]
    },
    imagesByDevice: state => (id) => {
      console.log('ImagesByDevice', id, state.devicesById[id])
      return state.devicesById[id] ? state.devicesById[id].images : []
    }
  },
  mutations: {
    clear(state) {
      state.devicesByEvent = {}
      state.devicesById = {}
    },
    setForEvent (state, params) {
      // Extract id from params.devices
      Vue.set(state.devicesByEvent, params.eventid, [])

      params.devices.forEach(d => {
        Vue.set(state.devicesById, d.id, d)
        state.devicesByEvent[d.eventid].push(d.id)
      })
    },
    add (state, params) {
      if (params.id) {
        if (!state.devicesByEvent[params.eventid]) {
          Vue.set(state.devicesByEvent, params.eventid, [])
        }

        // If not already in the list for the event, add it.
        if (!state.devicesByEvent[params.eventid].includes(params.id)) {
          state.devicesByEvent[params.eventid].push(params.id)
        }

        Vue.set(state.devicesById, params.id, params)
        console.log('Set images', params.images)
      }

      return params
    },
    remove (state, id) {
      const device = state.devicesById[id]

      if (state.devicesByEvent[device.eventid]) {
        let newarr = state.devicesByEvent[device.eventid].filter((a) => {
          return a.id !== id
        })

        Vue.set(state.devicesByEvent, device.eventid, newarr)
      }

      Vue.delete(state.devicesById, id)
    },
    addURL(state, params) {
      const device = state.devicesById[params.id]

      let exists = device.urls.findIndex(u => {
        return u.id === params.id
      })

      if (exists !== -1) {
        device.urls[exists] = params.url
      } else {
        device.urls.push(params.url)
      }

      Vue.set(state.devicesById, params.id, device)
    },
    removeURL(state, params) {
      const device = state.devicesById[params.id]

        device.urls = device.urls.filter(u => {
          return u.id !== params.url.id
        })

      Vue.set(state.devicesById, params.id, device)
    },
  },
  actions: {
    clear({commit}) {
      commit('clear')
    },
    async fetch({commit}, id) {
      console.log('Fetch', id)
      const ret = await axios.get('/api/v2/devices/' + id)
      console.log('Fetch returned', ret)

      if (ret && ret.data && ret.data.data) {
        commit('add', ret.data.data)
      }
    },
    setForEvent ({commit}, params) {
      commit('setForEvent', params)
    },
    async add ({commit, dispatch, rootGetters}, params) {
      const formData = new FormData()

      if (params.event_id) {
        params['eventid'] = params['event_id']
        delete params['event_id']
      }

      for (var key in params) {
        if (params[key]) {
          formData.append(key, params[key]);
        }
      }

      let ret = await axios.post('/api/v2/devices?api_token=' + rootGetters['auth/apiToken'], formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      })

      let id = null

      if (ret && ret.data) {
        id = ret.data.id
      }

      let created = null

      if (ret && ret.data && ret.data.device) {
        // We have been returned the device object from the server.  Add it into the store, and lo!  All our
        // stats and views will update.
        commit('add', ret.data.device)

        // Update our stats
        dispatch('events/setStats', {
          idevents: params.eventid,
          stats: ret.data.stats
        }, {
          root: true
        })
      }

      return created
    },
    async edit ({commit, dispatch, rootGetters}, params) {
      const formData = new FormData()

      for (var key in params) {
        if (params[key]) {
          formData.append(key, params[key]);
        }
      }

      let ret = await axios.post('/api/v2/devices/' + params.id + '?api_token=' + rootGetters['auth/apiToken'] + '&_method=PATCH', formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      })

      if (ret && ret.data && ret.data.device) {
        // We have been returned the device object from the server.  Update it in the store, and lo!  All our
        // stats and views will update.
        commit('add', ret.data.device)

        // Update our stats
        dispatch('events/setStats', {
          idevents: params.eventid,
          stats: ret.data.stats
        }, {
          root: true
        })
      }
    },
    async delete ({commit, dispatch, rootGetters}, id) {
      const device = rootGetters['devices/byId'](id)
      const eventid = device.eventid

      let ret = await axios.delete('/api/v2/devices/' + id + '?api_token=' + rootGetters['auth/apiToken'])

      console.log("Delete device returned", ret)

      commit('remove', id)

      // Update our stats
      dispatch('events/setStats', {
        idevents: eventid,
        stats: ret.data.stats
      }, {
        root: true
      })
    },
    async addURL ({commit, rootGetters}, params) {
      const ret = await axios.post('/device-url', {
        device_id: params.id,
        url: params.url.url,
        source: params.url.source
      }, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success) {
        // Update our store with the new URL.
        const newurl = {
          id: ret.data.id,
          url: params.url.url,
          source: params.url.source
        }

        commit('addURL', {
          device_id: params.id,
          url: newurl
        })
      }
    },
    async editURL ({commit, rootGetters}, params) {
      const ret = await axios.put('/device-url/' + params.url.id, {
        url: params.url.url,
        source: params.url.source
      }, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success) {
        // Update our store with the new URL.
        commit('addURL', {
          device_id: params.id,
          url: params.url
        })
      }
    },
    async deleteURL ({commit}, params) {
      const ret = await axios.delete('/device-url/' + params.url.id, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success) {
        // Update our store with the new URL.
        commit('removeURL', {
          device_id: params.id,
          url: params.url,
        })
      }
    },
    async deleteImage({commit, rootGetters}, params) {
      if (params.id && params.idxref) {
        const url = '/device/image/delete/' + params.id + '/' + params.idxref
        const ret = await axios.get(url, {
          headers: {
            'X-CSRF-TOKEN': rootGetters['auth/CSRF']
          }
        })
     }
    }
  },
}