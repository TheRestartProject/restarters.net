<template>
  <transition name="recent">
    <b-tr v-if="!editing" :key="'summary-' + device.id">
      <b-td>
        <span v-if="device.item_type">
          {{ device.item_type }}
        </span>
        <em v-else class="text-muted">
          -
        </em>
      </b-td>
      <b-td>
        <h3 class="noheader">
          {{ translatedCategoryName }}
        </h3>
        <div class="d-td-cell d-md-none">
          <div :class="badgeClass + ' d-block d-md-none'">
            {{ status }}
          </div>
          <b-img v-if="sparePartsNeeded" src="/images/tick.svg" class="icon spare-parts" />
        </div>
      </b-td>
      <b-td class="d-none d-md-table-cell" v-if="powered">
          {{ device.brand }}
      </b-td>
      <b-td v-if="canedit && powered" class="d-td-cell d-md-none">
        <div>
          <span class="pl-0 pl-md-2 pr-4 clickme edit" @click="editDevice">
            <b-img class="icon edit" src="/icons/edit_ico_green.svg" />
          </span>
          <span class="pr-2 clickme" @click="deleteConfirm">
            <b-img class="icon" src="/icons/delete_ico_red.svg" />
          </span>
        </div>
      </b-td>
      <b-td v-if="canedit && !powered" class="d-td-cell d-md-none">
        <div>
          <span class="pl-0 pl-md-2 pr-2 clickme edit" @click="editDevice">
            <b-img class="icon" src="/icons/edit_ico_green.svg" />
          </span>
          <span class="pl-2 pr-2 clickme" @click="deleteConfirm">
            <b-img class="icon" src="/icons/delete_ico_red.svg" />
          </span>
        </div>
      </b-td>
      <b-td class="d-none d-md-table-cell device-age-summary">
        {{ parseFloat(device.age) ? parseFloat(device.age) : '-' }}
      </b-td>
      <b-td class="d-none d-md-table-cell">
        {{ device.short_problem }}
      </b-td>
      <b-td class="d-none d-md-table-cell">
        <span :class="badgeClass">
          {{ status }}
        </span>
      </b-td>
      <b-td class="text-center d-none d-md-table-cell">
        <b-img v-if="sparePartsNeeded" src="/images/tick.svg" class="icon spare-parts-tick" />
      </b-td>
      <b-td v-if="canedit" class="text-right d-none d-md-table-cell">
        <div class="d-flex">
          <span class="pl-0 pl-md-2 pr-4 clickme edit" @click="editDevice">
            <b-img class="icon" src="/icons/edit_ico_green.svg" />
          </span>
            <span class="pr-2 clickme" @click="deleteConfirm">
            <b-img class="icon" src="/icons/delete_ico_red.svg" />
          </span>
        </div>
        <ConfirmModal :key="'modal-' + id" ref="confirmDelete" @confirm="deleteConfirmed" :message="__('devices.confirm_delete')" />
      </b-td>
    </b-tr>
    <b-tr v-else :key="'editing-' + id">
      <b-td colspan="8" class="p-0">
        <EventDevice :key='bump' :id="id" :powered="powered" :add="false" :edit="true" :clusters="clusters" :eventid="idevents" :brands="brands" :barrier-list="barrierList" @close="close" />
      </b-td>
    </b-tr>
  </transition>
</template>
<script>
import event from '../mixins/event'
import { FIXED, REPAIRABLE, END_OF_LIFE, SPARE_PARTS_MANUFACTURER, SPARE_PARTS_THIRD_PARTY } from '../constants'
import ConfirmModal from './ConfirmModal'
import EventDevice from './EventDevice'

export default {
  components: {EventDevice, ConfirmModal},
  mixins: [ event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    id: {
      type: Number,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    recent: {
      type: Boolean,
      required: false,
      default: true
    },
    clusters: {
      type: Array,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: false,
      default: null
    },
    barrierList: {
      type: Array,
      required: false,
      default: null
    },
  },
  data () {
    return {
      editing: false,
      saveScroll: null,
      bump: 0,
    }
  },
  computed: {
    device() {
      return this.id ? this.$store.getters['devices/byId'](this.id) : null
    },
    translatedCategoryName() {
      return this.$lang.get('strings.' + this.device.category.name)
    },
    powered() {
      return this.device.category && this.device.category.powered
    },
    status() {
      switch (this.device.repair_status) {
        case FIXED: return this.$lang.get('partials.fixed');
        case REPAIRABLE: return this.$lang.get('partials.repairable');
        case END_OF_LIFE: return this.$lang.get('partials.end');
        default: return null
      }
    },
    badgeClass() {
      switch (this.device.repair_status) {
        case FIXED:
          return 'badge badge-success'
        case REPAIRABLE:
          return 'badge badge-warning'
        case END_OF_LIFE:
          return 'badge badge-danger'
        default:
          return null
      }
    },
    sparePartsNeeded() {
      return this.device.spare_parts === SPARE_PARTS_MANUFACTURER || this.device.spare_parts === SPARE_PARTS_THIRD_PARTY
    },
  },
  methods: {
    deleteConfirm() {
      this.$refs.confirmDelete.show()
    },
    deleteConfirmed() {
      this.$store.dispatch('devices/delete', this.device.id)
    },
    editDevice() {
      // When we are editing, the original row disappears to be replaced by a new row containing just the editable
      // device.  This is arguably the right thing anyway, but matters are complicated:
      // - we want a transition on the row for when new devices are added.
      // - transitions can only have a single child.
      // - transition-group exists to solve this, but that inserts a DOM tag.  But we're within a table, and
      //   inserting a DOM tag breaks the table layout.
      // So swapping out the row enables us to use transitions within the table structure.
      this.editing = true

      // Save the scroll position.
      this.saveScroll = window.scrollY
    },
    close() {
      this.editing = false
      this.bump++
      window.scrollY = this.saveScroll
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

.recent-enter, .recent-leave-to {
  opacity: 0;
}

.recent-enter-active, .recent-enter-leave {
  transition: opacity 3s;
}

.badge {
  width: 90px;
  padding: 0;
  border-radius: 0;
  font-size: small;
  line-height: 2;
  text-transform: uppercase;
}
</style>
