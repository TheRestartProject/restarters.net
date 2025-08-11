<template>
  <div>
    <h2 class="mt-2 mb-2">
      {{ __('events.environmental_impact') }}
      <span v-b-popover.html="__('events.impact_calculation')">
        <b-img class="ml-2 icon-info clickable" :src="imageUrl('/icons/info_ico_green.svg')" />
      </span>
    </h2>
    <div class="impact-container">
      <StatsValue
        :count="Math.ceil(stats.waste_total)"
        icon="trash"
        size="md"
        title="partials.waste_prevented"
        unit="kg"
        class="impact-waste"
      />
      <div v-if="notincluded" class="d-flex justify-content-end">
        <div class="impact-notincluded">
          <div class="impact-notincluded-content p-1">
            {{ notincluded }}
          </div>
        </div>
      </div>
      <StatsValue
            :count="Math.round(stats.co2_total)"
            icon="cloud_empty"
            size="lg"
            title="partials.co2"
            :description="equivalent_consumer(Math.round(stats.co2_total))"
            unit="kg"
            class="impact-co2"
            :popover="popover_consumer(Math.round(stats.co2_total))"
            share
            @share="share"
      />
      <StatsShareModal
        ref="shareModal"
        :count="Math.round(stats.co2_total)" />
    </div>
  </div>
</template>
<script>
import StatsValue from './StatsValue.vue'
import co2equivalent from '../mixins/co2equivalent'
import images from '../mixins/images'
const StatsShareModal = () => import('./StatsShareModal.vue')

export default {
  components: {StatsValue, StatsShareModal},
  mixins: [ co2equivalent, images ],
  props: {
    stats: {
      required: true,
      type: Object
    },
    statsEntity: {
      required: true,
      type: String
    }
  },
  computed: {
    notincluded() {
      const langSource = this.statsEntity + 's'; // which lang file to look in, i.e. events or groups.
      let ret = []

      if (this.stats.dead_devices) {
        ret.push(this.$lang.choice('partials.to_be_recycled', this.stats.dead_devices, {
          value: this.stats.dead_devices
        }))
      }

      if (this.stats.repairable_devices) {
        ret.push(this.$lang.choice('partials.to_be_repaired', this.stats.repairable_devices, {
          value: this.stats.repairable_devices
        }))
      }

      if (this.stats.no_weight) {
        ret.push(this.$lang.choice('partials.no_weight', this.stats.no_weight, {
          value: this.stats.no_weight
        }))
      }

      if (!ret.length) {
        return null
      } else if (ret.length === 1) {
        // events.not_counting, groups.not_counting
        const intro = this.$lang.choice(langSource + '.not_counting', this.stats.no_weight)
        return intro + ' ' + ret[0] + '.'
      } else {
        const intro = this.$lang.choice(langSource + '.not_counting', this.stats.no_weight)
        const first = ret.slice(0, -1)
        const last = ret[ret.length - 1]

        return intro + ' ' + first.join(', ') + ' ' + this.$lang.get('and') + ' ' + last + '.'
      }
    }
  },
  methods: {
    share() {
      this.$refs.shareModal.show()
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.impact-container {
  display: grid;
  grid-template-columns: 1fr;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 2fr;
  }
}

.impact-waste {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 1;
  grid-column-end: 2;
  margin-bottom: 20px;

  @include media-breakpoint-up(md) {
    margin-bottom: 0px;
  }
}

.impact-notincluded {
  display: grid;
  grid-row-start: 3;
  grid-row-end: 4;
  grid-column-start: 1;
  grid-column-end: 2;

  @include media-breakpoint-up(md) {
    grid-row-start: 2;
    grid-row-end: 2;
    align-content: end;
  }
}

.impact-co2 {
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 1;
  grid-column-end: 2;
  margin-bottom: 20px;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 3;
    grid-column-start: 2;
    grid-column-end: 3;
    margin-bottom: 0px;
    margin-left: 20px;
  }
}

.impact-notincluded-content {
  color: $brand-light;
  font-size: 11px;
  border: 1px solid $brand-light;
}

</style>
