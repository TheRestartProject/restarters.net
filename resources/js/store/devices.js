import Vue from 'vue'

const axios = require('axios')

export default {
  namespaced: true,
  state: {
    // Object indexed by event id containing array of devices.  Use object rather than array so that it's sparse.
    devices: {},

    // Object indexed by device id containing list of images.
    images: {}
  },
  getters: {
    byEvent: state => idevents => {
      return state.devices[idevents]
    },
    imagesByDevice: state => (iddevices) => {
      return state.images[iddevices] || []
    }
  },
  mutations: {
    clear(state) {
      state.devices = {}
      state.images = {}
    },
    set (state, params) {
      Vue.set(state.devices, params.idevents, params.devices)

      params.devices.forEach(d => {
        Vue.set(state.images, d.iddevices, d.images)
      })
    },
    add (state, params) {
      let exists = false

      if (params.iddevices) {
        if (!state.devices[params.idevents]) {
          Vue.set(state.devices, params.idevents, [])
        }

        state.devices[params.idevents].forEach((d, i) => {
          if (d.iddevices === params.iddevices) {
            // Found it there already.
            Vue.set(state.devices[params.idevents], i, params)
            Vue.set(state.images, params.iddevices, params.images)
            exists = true
          }
        })
      }

      if (!exists) {
        // Append the new device to the existing list.
        state.devices[params.idevents].push(params)
        Vue.set(state.images, params.iddevices, params.images)
      }
    },
    remove (state, params) {
      if (state.devices[params.idevents]) {
        let newarr = state.devices[params.idevents].filter((a) => {
          return a.iddevices !== params.iddevices
        })

        Vue.set(state.devices, params.idevents, newarr)
        Vue.delete(state.images, params.iddevices)
      }
    },
    addURL(state, params) {
      // Fix the device.  This isn't very efficient but the numbers involved are never very large.
      for (let idevents in state.devices) {
        const devices = state.devices[idevents]

        for (let dix = 0; dix < state.devices[idevents].length; dix++) {
          const device = devices[dix]

          if (params.device_id === device.iddevices) {
            let exists = device.urls.findIndex(u => {
              return u.id === params.id
            })

            if (exists !== -1) {
              device.urls[exists] = params.url
            } else {
              device.urls.push(params.url)
            }

            devices[dix] = device
            Vue.set(state.devices, idevents, devices)
          }
        }
      }
    },
    removeURL(state, params) {
      for (let idevents in state.devices) {
        const devices = state.devices[idevents]

        for (let dix = 0; dix < state.devices[idevents].length; dix++) {
          const device = devices[dix]

          if (params.device_id === device.iddevices) {
            device.urls = device.urls.filter(u => {
              return u.id !== params.url.id
            })

            devices[dix] = device
            Vue.set(state.devices, idevents, devices)
          }
        }
      }
    },
    setImages(state, params) {
      Vue.set(state.images, params.iddevices, params.images)
    },
    removeImage(state, params) {
      Vue.set(state.images, params.iddevices, state.images[params.iddevices].filter(u => {
        return u.idxref !== params.idxref
      }))
    },
  },
  actions: {
    clear({commit}) {
      commit('clear')
    },
    set ({commit}, params) {
      commit('set', params)
    },
    async add ({commit, dispatch, rootGetters}, params) {
      let created = null

      let ret = await axios.post('/device/create', params, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success && ret.data.devices) {
        // We have been returned the device objects from the server.  Add them into the store, and lo!  All our
        // stats and views will update.
        created = ret.data.devices

        created.forEach(d => {
          commit('add', d)
        })

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

      return created
    },
    async edit ({commit, dispatch, rootGetters}, params) {
      let ret = await axios.post('/device/edit/' + params.iddevices, params, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      if (ret && ret.data && ret.data.success && ret.data.device) {
        // We have been returned the device objects from the server.  Add them into the store, and lo!  All our
        // stats and views will update.
        commit('add', ret.data.device)

        // Update our stats
        dispatch('events/setStats', {
          idevents: params.event_id,
          stats: ret.data.stats
        }, {
          root: true
        })
      }
    },
    async delete ({commit, dispatch, rootGetters}, params) {
      const ret = await axios.get('/device/delete/' + params.iddevices, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      console.log("Delete device returned", ret)
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
        throw 'Server request failed'
      }
    },
    async addURL ({commit, rootGetters}, params) {
      const ret = await axios.post('/device-url', {
        device_id: params.iddevices,
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
          device_id: params.iddevices,
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
          device_id: params.iddevices,
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
          device_id: params.iddevices,
          url: params.url,
        })
      }
    },
    setImages({commit}, params) {
      commit('setImages', params)
    },
    async deleteImage({commit, rootGetters}, params) {
      console.log("Delete image", params)
      if (params.iddevices && params.idxref) {
        const url = '/device/image/delete/' + params.iddevices + '/' + params.idxref
        const ret = await axios.get(url, {
          headers: {
            'X-CSRF-TOKEN': rootGetters['auth/CSRF']
          }
        })

        // This isn't a proper API call, and returns success/failure via a redirect to another page.  Assume
        // it works until we have a better API.
        commit('removeImage', params)
      }
    }
  },
}