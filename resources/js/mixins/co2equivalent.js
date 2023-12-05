export default {
  methods: {
    equivalent_consumer(co2) {
      let ret, val, key

      if (co2 >= 13501) {
        // Large value, compare to hectares.  12 tonnes per hectare per year sourced from
        // https://winrock.org/flr-calculator/
        val = Math.round(co2 / 12000)
        key = 'partials.emissions_equivalent_consume_high'
      }  else {
        // Small value, compare to seedlings.  60kg sourced from https://www.epa.gov/energy/greenhouse-gas-equivalencies-calculator
        val = Math.round(co2 / 60)
        key = 'partials.emissions_equivalent_consume_low'
      }

      ret = this.$lang.choice(key, val, {
        value: '<span class="text-brand-light font-weight-bold">' + val + '</span>'
      })

      return ret
    }
  }
}