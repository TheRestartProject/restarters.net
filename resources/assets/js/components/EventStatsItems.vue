<template>
  <div>
    <h2 class="mt-2 mb-2">Items fixed</h2>
    <div class="d-flex justify-content-left flex-wrap">
      <EventStatsValue :count="stats.fixed_devices" icon="fixed" size="sm" variant="primary" />
      <EventStatsValue :count="stats.fixed_devices" icon="powered" size="md" title="devices.powered_items"/>
      <EventStatsValue :count="stats.fixed_devices" icon="unpowered" size="md" title="devices.unpowered_items"/>
    </div>
  </div>
</template>
<script>
// TODO Unpowered stat
import EventStatsValue from './EventStatsValue'
export default {
  components: {EventStatsValue},
  props: {
    stats: {
      required: true,
      type: Object
    }
  }
}
</script>