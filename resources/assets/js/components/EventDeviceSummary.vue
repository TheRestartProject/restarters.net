<template>
  <transition name="recent">
    <b-tr>
      <b-td>
        <h3 class="noheader">
          {{ device.category.name }}
        </h3>
      </b-td>
      <b-td v-if="powered">
        {{ device.model }}
      </b-td>
      <b-td v-if="powered">
        {{ device.brand }}
      </b-td>
      <b-td v-if="!powered">
        {{ device.item_type }}
      </b-td>
      <b-td>
        {{ device.age }}
      </b-td>
      <b-td>
        {{ device.shortProblem }}
      </b-td>
      <b-td>
        {{ status }}
      </b-td>
      <b-td class="text-center">
        <b-img v-if="!sparePartsNeeded" src="/images/tick.svg" class="icon" />
      </b-td>
      <b-td v-if="canedit">
        <b-img class="icon mr-3" src="/icons/edit_ico_green.svg" />
        <b-img class="icon" src="/icons/delete_ico_red.svg" />
      </b-td>
    </b-tr>
  </transition>
</template>
<script>
// TODO Edit / delete
import event from '../mixins/event'
import { FIXED, REPAIRABLE, END_OF_LIFE, SPARE_PARTS_MANUFACTURER, SPARE_PARTS_THIRD_PARTY } from '../constants'

export default {
  mixins: [ event ],
  props: {
    device: {
      type: Object,
      required: true
    },
    recent: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  computed: {
    powered() {
      return this.device.category && this.device.category.powered
    },
    status() {
      switch (this.device.repair_status) {
        case FIXED: return this.$lang.get('partials.fixed'); break;
        case REPAIRABLE: return this.$lang.get('partials.repairable'); break;
        case END_OF_LIFE: return this.$lang.get('partials.end'); break;
        default: return null
      }
    },
    sparePartsNeeded() {
      return this.device.spare_parts === SPARE_PARTS_MANUFACTURER || this.device.spare_parts === SPARE_PARTS_THIRD_PARTY
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.icon {
  width: 21px;
  border: none;
}

.noheader {
  //We use an H3 for accessibility but we don't want it to look like one.
  font-weight: normal;
  font-size: 16px;
  line-height: 1.5;
  margin: 0;
}

.recent-enter {
  opacity: 0;
}

.recent-enter-active {
  transition: opacity 3s;
}
</style>