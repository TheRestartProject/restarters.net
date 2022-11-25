import axios from 'axios'

export default {
  namespaced: true,
  state: {
    apiToken: null,
    CSRF: null
  },
  getters: {
    apiToken: state => {
      return state.apiToken
    },
    CSRF: state => {
      return state.CSRF
    }
  },
  mutations: {
    setApiToken(state, params) {
      state.apiToken = params.apiToken
    },
    setCSRF(state, params) {
      state.CSRF = params.CSRF
    },
  },
  actions: {
    setApiToken ({commit}, params) {
      commit('setApiToken', params)

      // Set this as a default header.  This means that subsequent axios requests will automatically include this header,
      // which is used by the API auth guard to authenticate the user.
      axios.defaults.headers.common.Authorization = 'Bearer ' + params.apiToken
    },
    setCSRF ({commit}, params) {
      commit('setCSRF', params)
    }
  }
}