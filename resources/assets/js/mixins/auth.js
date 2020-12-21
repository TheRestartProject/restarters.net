export default {
  props: {
    apiToken: {
      type: String,
      required: false,
      default: null
    }
  },
  created() {
    // The API token is passed as a prop, and we put it in the store for use anywhere we need to make API calls.
    this.$store.dispatch('auth/setApiToken', {
      apiToken: this.apiToken
    })
  }
}