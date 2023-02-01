export default {
  methods: {
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

      ret = this.$lang.choice(key, val, {
        value: val
      })

      return '<span class="text-brand-light font-weight-bold">' + ret + '</span>'
    }
  }
}