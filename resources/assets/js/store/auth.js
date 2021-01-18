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
    },
    setCSRF ({commit}, params) {
      commit('setCSRF', params)
    }
  }
}