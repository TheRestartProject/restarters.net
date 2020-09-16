export default {
  namespaced: true,
  state: {
    // Array indexed by event id containing array of attendees.
    list: []
  },
  getters: {
    byEvent: state => eventId => {
      return state.list[eventId]
    }
  },
  mutations: {
    set(state, params) {
      state.list[params.eventId] = params.attendees
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
  },
}