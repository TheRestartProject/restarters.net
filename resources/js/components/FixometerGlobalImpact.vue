<template>
  <div>
    <div class="fgi-layout">
      <FixometerLatestData :latest-data="latestData" class="latest-data" />
      <StatsValue :count="Math.round(impactData.waste_total)"
                  icon="trash"
                  size="md"
                  title="partials.waste_prevented"
                  unit="kg"
                  class="impact-waste"
      />
      <StatsValue :count="Math.round(impactData.co2_total)"
                  icon="cloud_empty"
                  size="lg"
                  title="partials.co2"
                  :description="equivalent_consumer(Math.round(impactData.co2_total))"
                  unit="kg"
                  class="impact-co2"
      />
<!--      Image disabled as needs a new version from designer.-->
<!--      image="/images/CO2_driving.png"-->

      <StatsValue :count="impactData.participants" icon="participants" size="md" title="groups.participants" class="impact-participants" />
      <StatsValue :count="impactData.hours_volunteered" icon="clock" size="md" title="groups.hours_volunteered" class="impact-hours-volunteered" />
      <StatsValue :count="impactData.fixed_powered" icon="powered" size="md" title="devices.powered_items" class="impact-powered" />
      <StatsValue :count="impactData.fixed_unpowered" icon="unpowered" size="md" title="devices.unpowered_items" class="impact-unpowered" />
    </div>
  </div>
</template>
<script>
import co2equivalent from '../mixins/co2equivalent'
import StatsValue from './StatsValue'
import FixometerLatestData from './FixometerLatestData'

export default {
  mixins: [ co2equivalent ],
  props: {
    latestData: {
      type: Object,
      required: true
    },
    impactData: {
      type: Object,
      required: true
    }
  },
  components: {FixometerLatestData, StatsValue},
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}

.fgi-layout {
  display: grid;
  grid-column-gap: 30px;
  grid-row-gap: 30px;

  grid-template-rows: auto auto auto auto auto;
  grid-template-columns: 1fr 1fr;

  .latest-data {
    grid-row: 1 / 2;
    grid-column: 1 / 3;
  }

  .impact-waste {
    grid-row: 2 / 3;
    grid-column: 1 / 3;
  }

  .impact-co2 {
    grid-row: 3 / 4;
    grid-column: 1 / 3;
  }

  .impact-participants {
    grid-row: 4 / 5;
    grid-column: 1 / 2;
  }

  .impact-hours-volunteered {
    grid-row: 4 / 5;
    grid-column: 2 / 3;
  }

  .impact-powered {
    grid-row: 5 / 6;
    grid-column: 1 / 2;
  }

  .impact-unpowered {
    grid-row: 5 / 6;
    grid-column: 2 / 3;
  }

  @include media-breakpoint-up(md) {
    grid-template-rows: 1fr 1fr;
    grid-template-columns: 2fr 2fr 1fr 1fr;

    .latest-data {
      grid-row: 1 / 2;
      grid-column: 1 / 2;
    }

    .impact-waste {
      grid-row: 2 / 3;
      grid-column: 1 / 2;
    }

    .impact-co2 {
      grid-row: 1 / 3;
      grid-column: 2 / 3;
    }

    .impact-participants {
      grid-row: 1 / 2;
      grid-column: 3 / 4;
    }

    .impact-hours-volunteered {
      grid-row: 1 / 2;
      grid-column: 4 / 5;
    }

    .impact-powered {
      grid-row: 2 / 3;
      grid-column: 3 / 4;
    }

    .impact-unpowered {
      grid-row: 2 / 3;
      grid-column: 4 / 5;
    }
  }
}
</style>