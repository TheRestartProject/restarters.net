export default {
  methods: {
    pluralise(str, val) {
      // Laravel blade templates allow pluralisation, the simplest case being of the form singular|plural.
      // lang.js doesn't have that, so do the simple case ourselves.
      const p = str.indexOf('|')

      if (p !== -1) {
        if (val === 1) {
          str = str.substring(0, p)
        } else {
          str = str.substring(p + 1)
        }
      }

      return str
    },
    equivalent_consumer(co2) {
      let ret, val, key

      if (co2 >= 3000) {
        // Large value, compare to driving.
        val = Math.round((1 / 0.12) * co2)
        key = 'partials.emissions_equivalent_consume_high'
      }  else {
        // Small value, compare to watching TV.
        val = Math.round((1 / 0.024) * co2 / 24)
        key = 'partials.emissions_equivalent_consume_low'
      }

      ret = this.$lang.get(key, {
        value: '<span class="text-brand-light font-weight-bold">' + val.toLocaleString() + '</span>'
      })

      console.log("Returning", this.pluralise(ret, val))
      return this.pluralise(ret, val)
    }
  }
}