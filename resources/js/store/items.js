import Vue from 'vue'

const axios = require('axios')

export default {
    namespaced: true,
    fetching: null,
    state: {
        // Array indexed by event id containing array of attendees.
        list: []
    },
    getters: {
        list: state => {
            return state.list
        }
    },
    mutations: {
        setList(state, list) {
            state.list = []

            list.forEach(e => {
                state.list.push(e)
            })
        },
    },
    actions: {
        async fetch({ commit, state }) {
            // Item types don't change often, so only fetch if we don't have them in store.
            if (!state.fetching && !state.list.length) {
                state.fetching = axios.get('/api/v2/items')
            }

            let ret = await state.fetching
            if (ret && ret.data) {
                commit('setList', ret.data.data)
            }
        }
    },
}