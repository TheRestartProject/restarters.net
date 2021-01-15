<template>
  <div class="mb-2">
    <FixometerHeading />
    <FixometerGlobalImpact :latest-data="latestData" :impact-data="impactData" class="mt-4" />
  </div>
</template>
<script>
import FixometerHeading from './FixometerHeading'
import FixometerGlobalImpact from './FixometerGlobalImpact'
import auth from '../mixins/auth'

export default {
  components: {FixometerGlobalImpact, FixometerHeading},
  mixins: [ auth ],
  props: {
    latestData: {
      type: Object,
      required: true
    },
    impactData: {
      type: Object,
      required: true
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

</style>