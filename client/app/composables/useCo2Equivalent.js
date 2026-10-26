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
// popoverConsumer() is popover_consumer(): what the comparison's trees are,
// shown in the info popover beside it on the group and event pages.
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
    return co2 >= 13501
      ? t('partials.emissions_equivalent_consume_high_explanation')
      : t('partials.emissions_equivalent_consume_low_explanation')
  }

  return { equivalentConsumer, popoverConsumer }
}
