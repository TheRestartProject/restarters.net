<template>
  <div>
    <div class="border">
      <div :class="{
        'title': true,
        'd-flex': true,
        'justify-content-between': true,
        'expanded' : expandedItems
      }">
        <div class="flex-grow-1">
          <h3 class="text-uppercase header pl-2 mt-3 font-weight-bold text-center">
            {{ translatedItemAndRepairInfo }}
          </h3>
        </div>
        <b-btn variant="link" class="pr-1 pl-0" @click="toggleItems">
          <img class="icon" v-if="expandedItems" src="/images/minus-icon-brand.svg" alt="Collapse" />
          <img class="icon" v-else src="/images/add-icon-brand.svg" alt="Expand" />
        </b-btn>
      </div>
      <b-collapse id="collapse-item" v-model="expandedItems">
        <b-card no-body>
          <b-card-body class="p-2">
            <b-form-group :label="translatedCategory">
              <DeviceCategorySelect :category.sync="category" :clusters="clusters" :powered="powered" />
            </b-form-group>
            <b-form-group v-if="powered" :label="translatedModel">
              <DeviceModel :model.sync="model" />
            </b-form-group>
            <b-form-group  v-if="powered" :label="translatedBrand">
              <DeviceBrandSelect :brand.sync="brand" :brands="brands" />
            </b-form-group>
            <b-form-group v-if="!powered" :label="translatedModelOrType">
              <DeviceModel :model.sync="item_type" />
            </b-form-group>
            <div class="w-100 device-select-row">
              <b-form-group :label="translatedStatus">
                <multiselect
                    v-model="status"
                    :options="statusOptions"
                    track-by="id"
                    label="text"
                    :multiple="false"
                    :allow-empty="false"
                    deselect-label=""
                    :taggable="false"
                    selectLabel=""
                />
              </b-form-group>
              <div />
            </div>
            <div class="w-100 device-select-row">
              <b-form-group :label="translatedSearchAssessmentComments">
                <b-input :model.sync="comments" />
              </b-form-group>
              <div />
            </div>
          </b-card-body>
        </b-card>
      </b-collapse>
    </div>
    <div class="border">
      <div :class="{
        'title': true,
        'd-flex': true,
        'justify-content-between': true,
        'expanded' : expandedEvents
      }">
        <div class="flex-grow-1">
          <h3 class="text-uppercase header pl-2 mt-3 font-weight-bold text-center">
            {{ translatedEventInfo }}
          </h3>
        </div>
        <b-btn variant="link" class="pr-1 pl-0" @click="toggleEvents">
          <img class="icon" v-if="expandedEvents" src="/images/minus-icon-brand.svg" alt="Collapse" />
          <img class="icon" v-else src="/images/add-icon-brand.svg" alt="Expand" />
        </b-btn>
      </div>
      <b-collapse id="collapse-item" v-model="expandedEvents">
        <b-card no-body>
          <b-card-body class="p-2">
            <p class="card-text">Collapse contents Here TODO</p>
          </b-card-body>
        </b-card>
      </b-collapse>
    </div>
  </div>
</template>
<script>
import DeviceCategorySelect from './DeviceCategorySelect'
import DeviceModel from './DeviceModel'
import DeviceBrandSelect from './DeviceBrandSelect'
import { END_OF_LIFE, FIXED, REPAIRABLE } from '../constants'

export default {
  components: {DeviceBrandSelect, DeviceModel, DeviceCategorySelect},
  props: {
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
    powered: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      expandedItems: false,
      expandedEvents: false,
      category: null,
      model: null,
      brand: null,
      status: null,
      item_type: null
    }
  },
  computed: {
    translatedItemAndRepairInfo() {
      return this.$lang.get('devices.item_and_repair_info')
    },
    translatedEventInfo() {
      return this.$lang.get('devices.event_info')
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedBrand() {
      return this.$lang.get('devices.brand')
    },
    translatedModel() {
      return this.$lang.get('devices.model')
    },
    translatedModelOrType() {
      return this.$lang.get('devices.model_or_type')
    },
    translatedStatus() {
      return this.$lang.get('devices.status')
    },
    translatedSearchAssessmentComments() {
      return this.$lang.get('devices.search_assessment_comments')
    },
    statusOptions () {
      return [
        {
          id: FIXED,
          text: this.$lang.get('partials.fixed')
        },
        {
          id: REPAIRABLE,
          text: this.$lang.get('partials.repairable')
        },
        {
          id: END_OF_LIFE,
          text: this.$lang.get('partials.end_of_life')
        }
      ]
    },
  },
  methods: {
    toggleItems() {
      this.expandedItems = !this.expandedItems
    },
    toggleEvents() {
      this.expandedEvents = !this.expandedEvents
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border {
  border: 1px solid $brand-light !important;
  box-shadow: 5px 5px 0 0 $brand-light;
}

.icon {
  width: 30px;
}

.header {
  font-size: 1rem;
  padding-bottom: 8px;
  color: $brand;
}

.expanded {
  .header {
    color: black;
  }
}

.title {
  color: black;
  background-color: $brand-grey-darker;
}

/deep/ .btn-link:hover {
  color: transparent;
}

/deep/ .btn:focus, .btn.focus {
  outline: 0;
  -webkit-box-shadow: none
}

/deep/ legend {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 0px;
  padding-left: 2px;
}
</style>