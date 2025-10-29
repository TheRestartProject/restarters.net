import Vue from 'vue'
import moment from 'moment'

import axios from 'axios'

function newToOld(e) {
  // We are in the frustrating position of having a half-written new API with sensible field names, but existing
  // Vue components that expect old-style field names.  We therefore sometimes need to convert the new API data
  // back into the old format which is expected.  In some bright future where we have shifted over to using the
  // new API completely, we can then migrate the Vue components to use the new field names and retire this function.
  // Similar code in event and device store.
  let ret = {
    idgroups: e.id,
    name: e.name,
    location: e.location,
    country: e.country,
    next_event: e.next_event ? new moment(e.next_event).format('Y-MM-DD') : null,
    all_confirmed_hosts_count: e.hosts,
    all_confirmed_restarters_count: e.restarters,
    image: e.image ? ('/uploads/mid_' + e.image) : null,
    networks: e.networks
  }

  return ret
}

function getLocale() {
  // The language is not yet migrated over to Vue, so pluck it from the DOM.
  const el = document.getElementById('language-current')

  return el.innerText.trim()
}

export default {
  namespaced: true,
  state: {
    // List of groups indexed by group id.  Use object rather than array so that it's sparse.
    list: {},

    // Groups requiring moderation.
    moderate: {},

    // Group tags.
    tags: {},

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
    listTags: state => {
      return Object.values(state.tags)
    },
    getModerate: state => state.moderate,
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
        list[e.idgroups || e.id] = e
      })

      state.list = list
    },
    setTags(state, params) {
      let list = {}
      params.tags.forEach(e => {
        list[e.id] = e
      })

      state.tags = list
    },
    setModerate(state, params) {
      params.forEach(e => {
        Vue.set(state.moderate, e.id, newToOld(e))
      })
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
        const group = getters.get(params.idgroups)
        group.all_restarters_count = ret.data.all_restarters_count
        group.all_hosts_count = ret.data.all_hosts_count
        group.ingroup = false
        commit('set', group)
      }
    },
    async getModerationRequired({commit, rootGetters}, params) {
      const apiToken = rootGetters['auth/apiToken']

      let ret = await axios.get('/api/v2/moderate/groups?api_token=' + apiToken + '&locale=' + getLocale())

      if (ret && ret.data) {
        commit('setModerate', ret.data)
      }
    },
    async list({commit}) {
      let ret = await axios.get('/api/v2/groups/names?locale=' + getLocale())
      if (ret && ret.data) {
        commit('setList', {
          groups: ret.data.data
        })
      }
    },
    async listTags({commit}) {
      let ret = await axios.get('/api/v2/groups/tags?locale=\' + getLocale()')
      if (ret && ret.data) {
        commit('setTags', {
          tags: ret.data.data
        })
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

        const apiToken = rootGetters['auth/apiToken']
        const url = '/api/v2/groups?api_token=' + apiToken

        let ret = await axios.post(url, formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })

        if (ret && ret.data) {
          id = ret.data.id
        }
      } catch (e) {
        console.error("Group create failed", e)
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

        let ret = await axios.post('/api/v2/groups/' + params.id + '?api_token=' + rootGetters['auth/apiToken'], formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })

        if (ret && ret.data) {
          id = ret.data.id
        }
      } catch (e) {
        console.error("Group edit failed", e)
      }

      return id
    },
    async fetch({ rootGetters, commit }, params) {
      try {
        let ret = await axios.get('/api/v2/groups/' + params.id + '?api_token=' + rootGetters['auth/apiToken'] + '&locale=' + getLocale())

        commit('set', ret.data.data)

        return ret.data.data
      } catch (e) {
        console.error("Group fetch failed", e)
      }
    }
  },
}