<template>
  <div>
    <div class="border">
      <div :class="{
        'title': true,
        'd-flex': true,
        'clickme': true,
        'justify-content-between': true,
        'expanded' : expandedItems
      }" @click="toggleItems">
        <div class="flex-grow-1">
          <h3 class="text-uppercase header pl-2 mt-3 font-weight-bold text-center">
            {{ __('devices.item_and_repair_info') }}
          </h3>
        </div>
        <b-btn variant="link" class="pr-1 pl-0">
          <img class="icon" v-if="expandedItems" :src="imageUrl('/images/minus-icon-brand.svg')" alt="Collapse" />
          <img class="icon" v-else :src="imageUrl('/images/add-icon-brand.svg')" alt="Expand" />
        </b-btn>
      </div>
      <b-collapse id="collapse-item" v-model="expandedItems">
        <b-card no-body>
          <b-card-body class="p-2">
            <b-form-group :label="__('devices.category')">
              <DeviceCategorySelect :category.sync="current_category" :clusters="clusters" :powered="powered" allow-empty />
            </b-form-group>
            <b-form-group v-if="powered" :label="__('devices.model')">
              <DeviceModel :model.sync="current_model" />
            </b-form-group>
            <b-form-group  v-if="powered" :label="__('devices.brand')">
              <DeviceBrand :brand.sync="current_brand" :brands="brands" allow-empty suppress-brand-warning />
            </b-form-group>
            <b-form-group v-if="!powered" :label="__('devices.model_or_type')">
              <DeviceModel :model.sync="current_item_type" />
            </b-form-group>
            <div class="w-100 device-select-row">
              <b-form-group :label="__('devices.status')">
                <multiselect
                    v-model="current_status"
                    :options="statusOptions"
                    track-by="id"
                    label="text"
                    :multiple="false"
                    allow-empty
                    selectLabel=""
                    deselect-label=""
                    :taggable="false"
                    :selectedLabel="__('partials.remove')"
                />
              </b-form-group>
              <div />
            </div>
            <div class="w-100 device-select-row">
              <b-form-group :label="__('devices.search_assessment_comments')">
                <b-input v-model="current_comments" />
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
        'clickme': true,
        'justify-content-between': true,
        'expanded' : expandedEvents
      }" @click="toggleEvents">
        <div class="flex-grow-1">
          <h3 class="text-uppercase header pl-2 mt-3 font-weight-bold text-center">
            {{ __('devices.event_info') }}
          </h3>
        </div>
        <b-btn variant="link" class="pr-1 pl-0">
          <img class="icon" v-if="expandedEvents" :src="imageUrl('/images/minus-icon-brand.svg')" alt="Collapse" />
          <img class="icon" v-else :src="imageUrl('/images/add-icon-brand.svg')" alt="Expand" />
        </b-btn>
      </div>
      <b-collapse id="collapse-item" v-model="expandedEvents">
        <b-card no-body>
          <b-card-body class="p-2">
            <b-form-group :label="__('devices.group')">
              <b-input v-model="current_group" />
            </b-form-group>
            <b-form-group :label="__('devices.from_date')">
              <b-form-datepicker class="datepicker" v-model="current_from_date" :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
            </b-form-group>
            <b-form-group :label="__('devices.to_date')">
              <b-form-datepicker class="datepicker" v-model="current_to_date" :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
            </b-form-group>
          </b-card-body>
        </b-card>
      </b-collapse>
    </div>
  </div>
</template>
<script>
import DeviceCategorySelect from './DeviceCategorySelect.vue'
import DeviceModel from './DeviceModel.vue'
import DeviceBrand from './DeviceBrand.vue'
import images from '../mixins/images'
import { END_OF_LIFE, FIXED, REPAIRABLE } from '../constants'

export default {
  components: {DeviceBrand, DeviceModel, DeviceCategorySelect},
  mixins: [images],
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
    },
    startExpandedItems: {
      type: Boolean,
      required: false,
      default: null
    },
    startExpandedEvents: {
      type: Boolean,
      required: false,
      default: null
    },
    category: {
      type: Number,
      required: false,
      default: null
    },
    brand: {
      type: String,
      required: false,
      default: null
    },
    model: {
      type: String,
      required: false,
      default: null
    },
    item_type: {
      type: String,
      required: false,
      default: null
    },
    status: {
      type: Number,
      required: false,
      default: null
    },
    comments: {
      type: String,
      required: false,
      default: null
    },
    group: {
      type: String,
      required: false,
      default: null
    },
    from_date: {
      type: String,
      required: false,
      default: null
    },
    to_date: {
      type: String,
      required: false,
      default: null
    },
    wiki: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      expandedItems: false,
      expandedEvents: false,
      current_model: null,
      current_brand: null,
      current_item_type: null,
      current_category: null,
      current_group: null,
      current_from_date: null,
      current_to_date: null,
      current_wiki: null
    }
  },
  computed: {
    // We need two-way binding on some of the props, which may change.  So we need to get the current value from
    // the prop, and if it changes then emit that.  We can't use the prop directly as a v-model.
    current_status: {
      get() {
        return this.statusOptions.find(v => v.id === this.status)
      },
      set(newVal) {
        this.$emit('update:status', newVal ? newVal.id : null)
      }
    },
    current_comments: {
      get() {
        return this.comments
      },
      set(newVal) {
        this.$emit('update:comments', newVal)
      }
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
  mounted () {
    this.expandedEvents = this.startExpandedEvents
    this.expandedItems = this.startExpandedItems
    this.current_model = this.model
    this.current_brand = this.brand
    this.current_item_type = this.item_type
    this.current_category = this.category
    this.current_group = this.group
    this.current_from_date = this.from_date
    this.current_to_date = this.to_date
    this.current_wiki = this.wiki
  },
  watch: {
    startExpandedItems (newVal) {
      this.expandedItems = newVal
    },
    startExpandedEvents (newVal) {
      this.expandedEvents = newVal
    },
    current_category(newVal) {
      this.$emit('update:category', newVal)
    },
    current_brand(newVal) {
      this.$emit('update:brand', newVal)
    },
    current_model(newVal) {
      this.$emit('update:model', newVal)
    },
    current_item_type(newVal) {
      this.$emit('update:item_type', newVal)
    },
    current_comments(newVal) {
      this.$emit('update:comments', newVal)
    },
    current_wiki(newVal) {
      this.$emit('update:wiki', newVal)
    },
    current_group(newVal) {
      this.$emit('update:group', newVal)
    },
    current_from_date(newVal) {
      this.$emit('update:from_date', newVal)
    },
    current_to_date(newVal) {
      this.$emit('update:to_date', newVal)
    }
  },
  methods: {
    toggleItems() {
      this.expandedItems = !this.expandedItems
      this.$emit('expandItems', this.expandedItems)
    },
    toggleEvents() {
      this.expandedEvents = !this.expandedEvents
      this.$emit('expandEvents', this.expandedEvents)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

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

::v-deep .btn-link:hover {
  color: transparent;
}

::v-deep .btn:focus, .btn.focus {
  outline: 0;
  -webkit-box-shadow: none
}

::v-deep legend {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 0px;
  padding-left: 2px;
}

.b-form-datepicker.form-control {
  padding: 0 10px;
}

::v-deep .datepicker {
  & label {
    padding-bottom: 0;
    border: 0;
    margin: 0;
    font-weight: normal;
  }

  .btn {
    padding: 0.4rem 0.3rem !important;
    min-width: initial;
  }

  .form-control {
    border: unset !important;
  }

  .btn-primary {
    background-color: $brand-orange !important;
    color: $black !important;
  }
}

.clickme {
  user-select: none;
}
</style>