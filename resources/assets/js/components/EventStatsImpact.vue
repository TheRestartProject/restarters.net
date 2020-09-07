<template>
  <div>
    <h2 class="mt-2 mb-2">Environmental Impact</h2>
    <div class="d-flex justify-content-between flex-wrap">
      <div class="d-flex justify-content-between flex-column">
        <EventStatsValue :count="stats.ewaste" icon="trash" size="md" title="partials.waste_prevented" unit="kg" />
        <div class="not_counting p-1">
          {{ notincluded }}
        </div>
      </div>
      <EventStatsValue :count="stats.co2" icon="cloud_empty" size="lg" title="partials.co2" subtitle="partials.powered_only" :description="equivalent_consumer(stats.co2)" unit="kg" />
    </div>
  </div>
</template>
<script>
import EventStatsItem from './EventStatsItem'
import EventStatsValue from './EventStatsValue'
import co2equivalent from '../mixins/co2equivalent'

export default {
  components: {EventStatsValue, EventStatsItem},
  mixins: [ co2equivalent ],
  props: {
    stats: {
      required: true,
      type: Object
    }
  },
  computed: {
    notincluded() {
      // We need to construct a
      let ret = []
      // TODO Unpowered

      if (this.stats.dead_devices) {
        ret.push(this.pluralise(this.$lang.get('partials.to_be_recycled', {
          value: this.stats.dead_devices
        }), this.stats.dead_devices))
      }

      if (this.stats.repairable_devices) {
        ret.push(this.pluralise(this.$lang.get('partials.to_be_repaired', {
          value: this.stats.repairable_devices
        }), this.stats.repairable_devices))
      }

      if (this.stats.no_weight) {
        ret.push(this.pluralise(this.$lang.get('partials.no_weight', {
          value: this.stats.no_weight
        }), this.stats.no_weight))
      }

      if (!ret.length) {
        return null
      } else if (ret.length === 1) {
        return ret[0]
      } else {
        const intro = this.pluralise(this.$lang.get('events.not_counting'), (this.stats.dead_devices + this.stats.repairable_devices + this.stats.no_weight))
        const first = ret.slice(0, -1)
        const last = ret[ret.length - 1]

        return intro + ' ' + first.join(', ') + ' ' + this.$lang.get('and') + ' ' + last + '.'
      }
    }
  }
}
// TODO ewaste needs to cover unpowered too
// TODO Info button
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.not_counting {
  color: $brand-light;
  font-size: 11px;
  border: 1px solid $brand-light;
  width: 152px;
}
</style>