export default {
  props: {
    apiToken: {
      type: String,
      required: false,
      default: null
    },
    csrf: {
      type: String,
      required: true
    }
  },
  created() {
    if (this.apiToken) {
      // The API token is passed as a prop, and we put it in the store for use anywhere we need to make API calls.
      this.$store.dispatch('auth/setApiToken', {
        apiToken: this.apiToken
      })
    }

    if (this.csrf) {
      this.$store.dispatch('auth/setCSRF', {
        CSRF: this.csrf
      })
    } else {
      console.error("No CSRF provided by blade template")
    }
  }
}