<template>
  <div>
    <h2 class="mt-2 mb-2">Environmental Impact</h2>
    <div class="d-flex justify-content-between flex-wrap">
      <EventStatsValue :count="stats.ewaste" icon="trash" size="md" title="partials.waste_prevented" unit="kg" />
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
    description() {
      return
    }
  }
}
// TODO Is ewaste the right stat?
// TODO Info button
// TODO "Not included"
</script>