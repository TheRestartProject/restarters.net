import { useI18n } from 'vue-i18n'

// Port of resources/js/mixins/co2equivalent.js's equivalent_consumer(): a
// human-scale comparison for a CO2e figure (kg), used by ImpactStats.vue
// under the headline CO2 stat. lang/en/partials.php's
// emissions_equivalent_consume_low/high strings use Laravel's `|`
// singular/plural choice format with a `:value` token - identical to
// vue-i18n's own `|` pluralization + `{value}` interpolation syntax, so the
// translation strings are reused completely unchanged (see
// i18n/locales/en.json, GENERATED from the same lang/en/partials.php).
//
// popover_consumer()'s explanation text is ported too but NOT wired into
// ImpactStats.vue yet - the legacy popover needs a b-popover equivalent
// that isn't part of this task's brief; recorded in api-gaps.md.
export function useCo2Equivalent() {
  const { t } = useI18n()

  function equivalentConsumer(co2) {
    let value, key

    if (co2 >= 13501) {
      // Compare to hectares of trees: 12 tonnes/hectare/year
      // (https://winrock.org/flr-calculator/).
      value = Math.round(co2 / 12000)
      key = 'partials.emissions_equivalent_consume_high'
    } else {
      // Compare to tree seedlings: 60kg/seedling
      // (https://www.epa.gov/energy/greenhouse-gas-equivalencies-calculator).
      value = Math.round(co2 / 60)
      key = 'partials.emissions_equivalent_consume_low'
    }

    return t(key, { value }, value)
  }

  function popoverConsumer(co2) {
    const key =
      co2 >= 13501
        ? 'partials.emissions_equivalent_consume_high_explanation'
        : 'partials.emissions_equivalent_consume_low_explanation'
    return t(key)
  }

  return { equivalentConsumer, popoverConsumer }
}
