export default {
  namespaced: true,
  state: {
    apiToken: null
  },
  getters: {
    apiToken: state => {
      return state.apiToken
    }
  },
  mutations: {
    setApiToken (state, params) {
      state.apiToken = params.apiToken
    },
  },
  actions: {
    setApiToken ({commit}, params) {
      commit('setApiToken', params)
    }
 }
}