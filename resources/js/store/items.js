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
            Vue.set(state, 'list', list)
        },
    },
    actions: {
        async fetch({ commit, state }) {
            // Check if we're running under Playwright tests via global variable or query param
            const isPlaywrightTest = (typeof window !== 'undefined' && window.PLAYWRIGHT_TEST === true) || 
                                    (typeof window !== 'undefined' && window.location.search.includes('playwright=true'))
            
            // Item types don't change often, so only fetch if we don't have them in store
            // Exception: always fetch fresh data during Playwright tests to get latest test data
            if (!state.fetching && (!state.list.length || isPlaywrightTest)) {
                const url = isPlaywrightTest ? '/api/v2/items?refresh_cache=true' : '/api/v2/items'
                state.fetching = axios.get(url)
            }

            let ret = await state.fetching
            if (ret && ret.data) {
                commit('setList', ret.data.data)
            }
        }
    },
}