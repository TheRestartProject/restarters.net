import Vue from 'vue'
import moment from 'moment'

const axios = require('axios')

function newToOld(e) {
  // We are in the frustrating position of having a half-written new API with sensible field names, but existing
  // Vue components that expect old-style field names.  We therefore sometimes need to convert the new API data
  // back into the old format which is expected.  In some bright future where we have shifted over to using the
  // new API completely, we can then migrate the Vue components to use the new field names and retire this function.
  // Similar code in group store.
  let ret = {
    idevents: e.id,
    venue: e.title,
    group: {
      idgroups: e.group.id,
      name: e.group.name,
      networks: e.group.networks
    },
    volunteers: e.stats.volunteers,
    allinvitedcount: e.stats.invited,
    event_start_utc: e.start,
    event_end_utc: e.end,
    event_date_local: e.start,
    timezone: e.timezone
  }

  const start = new moment(e.start)
  start.tz(e.timezone)
  ret.start_local = start.format('HH:mm')

  const end = new moment(e.end)
  end.tz(e.timezone)
  ret.end_local = end.format('HH:mm')

  return ret
}

export default {
  namespaced: true,
  state: {
    // List of events indexed by event id.  Use object rather than array so that it's sparse.
    list: {},
    moderate: {},

    // List of stats indexed by event id.
    stats: {}
  },
  getters: {
    get: state => idevents => {
      let ret = state.list[idevents]

      if (!ret) {
        // Might be an event we're moderating.
        ret = state.moderate[idevents]
      }

      return ret
    },
    getAll: state => state.list,
    getModerate: state => state.moderate,
    getByGroup: state => idgroups => {
      // null idgroups means fetch all
      return Object.values(state.list).filter(e => (idgroups === null || e.group === idgroups))
    },
    getStats: state => idevents => {
      return state.stats[idevents]
    }
  },
  mutations: {
    clear(state) {
      state.list = {}
    },
    set(state, params) {
      // There is a separate store for devices.  Make sure we don't accidentally use the list of devices returned
      // on the object, because that isn't updated dynamically.
      params.devices = null
      Vue.set(state.list, params.idevents, params)
    },
    setList(state, params) {
      params.events.forEach(e => {
        Vue.set(state.list, e.idevents, e)
      })
    },
    setModerate(state, params) {
      params.forEach(e => {
        Vue.set(state.moderate, e.id, newToOld(e))
      })
    },
    setStats(state, params) {
      Vue.set(state.stats, params.idevents, params.stats)
    },
    remove(state, params) {
      delete state.list[params.idevents]
    },
  },
  actions: {
    clear({commit}) {
      commit('clear')
    },
    set({commit}, params) {
      commit('set', params);
    },
    setList({commit}, params) {
      commit('setList', params);
    },
    setStats({commit}, params) {
      commit('setStats', params);
    },
    async delete({commit, rootGetters}, params) {
      let ret = await axios.post('/party/delete/' + params.idevents, {
        headers: {
          'X-CSRF-TOKEN': rootGetters['auth/CSRF']
        }
      })

      commit('remove', params)
    },
    async getModerationRequired({commit, rootGetters}, params) {
      const apiToken = rootGetters['auth/apiToken']

      let ret = await axios.get('/api/v2/moderate/events?api_token=' + apiToken)

      if (ret && ret.data) {
        commit('setModerate', ret.data)
      }
    },
    async create({ rootGetters, commit }, params) {
      let id = null
      try {
        const formData = new FormData()

        for (var key in params) {
          if (params[key]) {
            formData.append(key, params[key]);
          }
        }

        let ret = await axios.post('/api/v2/events?api_token=' + rootGetters['auth/apiToken'], formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })

        if (ret && ret.data) {
          id = ret.data.id
        }
      } catch (e) {
        console.error("Event create failed", e)
      }

      return id
    },
    async edit({ rootGetters, commit }, params) {
      let id = null
      try {
        const formData = new FormData()

        for (var key in params) {
          if (params[key]) {
            formData.append(key, params[key]);
          }
        }

        // We need to use a POST verb and override to a PATCH - see
        // https://stackoverflow.com/questions/55116787/laravel-patch-request-doesnt-read-axios-form-data
        formData.append('_method', 'PATCH');

        let ret = await axios.post('/api/v2/events/' + params.id + '?api_token=' + rootGetters['auth/apiToken'], formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })

        if (ret && ret.data) {
          id = ret.data.id
        }
      } catch (e) {
        console.error("Event edit failed", e)
      }

      return id
    },
    async fetch({ rootGetters, commit }, params) {
      try {
        let ret = await axios.get('/api/v2/events/' + params.id + '?api_token=' + rootGetters['auth/apiToken'])

        commit('set', ret.data.data)

        return ret.data.data
      } catch (e) {
        console.error("Events fetch failed", e)
      }
    },
    async fetchByGroup({ rootGetters, commit }, params) {
      try {
        let ret = await axios.get('/api/v2/groups/' + params.id + '/events?api_token=' + rootGetters['auth/apiToken'])

        commit('setList', {
          events: ret.data.data
        })

        return ret.data.data
      } catch (e) {
        console.error("Events fetch by group failed", e)
      }
    }
  },
}