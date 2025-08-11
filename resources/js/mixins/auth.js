import Vue from 'vue'
import VueCookies from 'vue-cookies'
Vue.use(VueCookies)

export default {
  props: {
    csrf: {
      type: String,
      required: false,
      default: null
    }
  },
  computed: {
    apiToken() {
      return this.$store.getters['auth/apiToken']
    }
  },
  created() {
    // We may get an API token from the server as a cookie.  Save it in the store so that it is then available for
    // making API calls.
    const apiToken = Vue.$cookies.get('restarters_apitoken');
    if (apiToken) {
      this.$store.dispatch('auth/setApiToken', {
        apiToken: apiToken
      })
    }

    if (this.csrf) {
      this.$store.dispatch('auth/setCSRF', {
        CSRF: this.csrf
      })
    }
  }
}