/**
 * This is a mixin for the form you want to validate.
 *
 * It does not include vuelidate, so include that too in your form component.
 */
export default {
  computed: {
    validationEnabled () {
      return this.$v.$dirty
    }
  },
  methods: {
    /**
     * This relies on a convention for setting "ref" attributes on form fields.
     * We then find this ref, scroll to it, and focus any input or textarea.
     */
    validationFocusFirstError () {
      const error = this.$v
        .$flattenParams()
        .map(({path}) => ({
          path,
          validator: this.$v[path],
          ref: this.$refs[path.join('__')]
        }))
        .find(({ref, validator}) => ref && validator.$error)

      if (!error) {
        return
      }

      console.log("First error", JSON.stringify(error))
      const {ref} = error
      const el = ref.$el
      el.scrollIntoView()

      const focusEl = el.querySelector('input, textarea')

      if (focusEl) {
        focusEl.focus()
      }
    }
  }
}
