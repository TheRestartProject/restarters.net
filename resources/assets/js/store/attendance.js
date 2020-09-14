export default {
  namespaced: true,
  state: {
    // Array indexed by event id containing array of attendees.
    list: []
  },
  getters: {
    byEvent: state => eventId => {
      console.log("Get by event", eventId, state.list)
      return state.list[eventId]
    }
  },
  mutations: {
    set(state, params) {
      console.log("Set", params)
      state.list[params.eventId] = params.attendees
    },
  },
  actions: {
    set({commit}, params) {
      commit('set', params);
    },
  },
}