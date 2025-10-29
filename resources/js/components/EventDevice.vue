<template>
  <b-form :class="{
      'edit-device': edit,
      'add-device': add
      }">
    <div class="device-info">
      <div class="br d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1 border-0">
          <h3 class="mt-2 mb-4">{{ __('devices.title_items') }}</h3>
          <DeviceType class="mb-2" :type.sync="currentDevice.item_type"
                      :icon-variant="add ? 'black' : 'brand'" :disabled="disabled"
                      :suppress-type-warning="suppressTypeWarning" :powered="powered"
                      :unknown.sync="unknownItemType"
                      :auto-focus="add"
          />
          <DeviceCategorySelect :class="{
            'mb-2': true,
            'border-thick': missingCategory,
            'suggested': suggested,
            }"
            :category.sync="currentDevice.category" :clusters="clusters" :powered="powered" :key="currentDevice.item_type"
            @open="suggested = false"
            :icon-variant="add ? 'black' : 'brand'" :disabled="disabled"
          />
          <DeviceBrand class="mb-2" :brand.sync="currentDevice.brand" :brands="brands" :disabled="disabled"
                       :suppress-brand-warning="suppressBrandWarning"/>
          <DeviceModel class="mb-2" :model.sync="currentDevice.model" :icon-variant="add ? 'black' : 'brand'"
                       :disabled="disabled"/>
          <DeviceAge :age.sync="currentDevice.age" :disabled="disabled"/>
          <DeviceWeight v-if="showWeight" :weight.sync="currentDevice.estimate" :disabled="disabled" :required="weightRequired" />
          <DeviceImages :id="idtouse" :add="add" :edit="edit" :disabled="disabled"
                        class="mt-2" @remove="removeImage($event)"/>
        </b-card>
      </div>
      <div class="d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1 border-0">
          <h3 class="mt-2 mb-4">{{ __('devices.title_repair') }}</h3>
          <DeviceRepairStatus :status.sync="currentDevice.repair_status" :steps.sync="currentDevice.next_steps"
                              :parts.sync="currentDevice.spare_parts" :barrier.sync="currentDevice.barrier"
                              :barrierList="barrierList" :disabled="disabled"/>
        </b-card>
      </div>
      <div class="bl d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1 border-0">
          <h3 class="mt-2 mb-4">{{ __('devices.title_assessment') }}</h3>
          <DeviceProblem :problem.sync="currentDevice.problem" class="mb-4" :icon-variant="add ? 'black' : 'brand'"
                         :disabled="disabled"/>
          <DeviceNotes :notes.sync="currentDevice.notes" class="mb-4" :icon-variant="add ? 'black' : 'brand'"
                       :disabled="disabled"/>
        </b-card>
      </div>
    </div>
    <b-alert :show="missingCategory" variant="danger">
      <p>{{ __('events.form_error') }}</p>
    </b-alert>
    <b-alert :show="axiosError !== null" variant="danger">
      <p>
        {{ axiosError }}
      </p>
    </b-alert>
    <div class="d-flex justify-content-center flex-wrap pt-4 pb-4">
      <b-btn variant="primary" class="mr-2" v-if="add" @click="addDevice">
        {{ __('partials.add_device') }}
      </b-btn>
      <b-btn variant="primary" class="mr-2" v-if="edit" @click="saveDevice">
        {{ __('partials.save') }}
      </b-btn>
      <b-btn variant="primary" class="mr-2" v-if="edit && deleteButton" @click="confirmDeleteDevice">
        {{ __('devices.delete_device') }}
      </b-btn>
      <DeviceQuantity v-if="add" :quantity.sync="currentDevice.quantity" class="flex-md-shrink-1 ml-2 mr-2"/>
      <b-btn variant="tertiary" class="ml-2 cancel" @click="cancel" v-if="cancelButton">
        {{ __('partials.cancel') }}
      </b-btn>
    </div>
    <ConfirmModal @confirm="deleteDevice" ref="confirm"/>
  </b-form>
</template>
<script>
import event from '../mixins/event'
import {
  SPARE_PARTS_MANUFACTURER,
  SPARE_PARTS_THIRD_PARTY,
  CATEGORY_MISC_POWERED, CATEGORY_MISC_UNPOWERED, NEXT_STEPS_DIY, NEXT_STEPS_PROFESSIONAL, NEXT_STEPS_MORE_TIME,
  PARTS_PROVIDER_MANUFACTURER,
  PARTS_PROVIDER_THIRD_PARTY, SPARE_PARTS_NOT_NEEDED
} from '../constants'
import DeviceCategorySelect from './DeviceCategorySelect.vue'
import DeviceBrand from './DeviceBrand.vue'
import DeviceModel from './DeviceModel.vue'
import DeviceWeight from './DeviceWeight.vue'
import DeviceAge from './DeviceAge.vue'
import DeviceType from './DeviceType.vue'
import DeviceRepairStatus from './DeviceRepairStatus.vue'
import DeviceProblem from './DeviceProblem.vue'
import DeviceNotes from './DeviceNotes.vue'
import DeviceQuantity from './DeviceQuantity.vue'
import FileUploader from './FileUploader.vue'
import DeviceImages from './DeviceImages.vue'
import ConfirmModal from './ConfirmModal.vue'

export default {
  components: {
    ConfirmModal,
    DeviceImages,
    FileUploader,
    DeviceQuantity,
    DeviceNotes,
    DeviceProblem,
    DeviceRepairStatus,
    DeviceType,
    DeviceAge,
    DeviceWeight,
    DeviceModel,
    DeviceBrand,
    DeviceCategorySelect
  },
  mixins: [event],
  props: {
    id: {
      type: Number,
      required: false,
      default: null
    },
    eventid: {
      type: Number,
      required: true
    },
    add: {
      type: Boolean,
      required: false,
      default: false
    },
    edit: {
      type: Boolean,
      required: false,
      default: false
    },
    deleteButton: {
      type: Boolean,
      required: false,
      default: false
    },
    cancelButton: {
      type: Boolean,
      required: false,
      default: true
    },
    powered: {
      // The server might return a number rather than a boolean.
      type: [Boolean, Number],
      required: false,
      default: false
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
      currentDevice: {},
      axiosError: null,
      missingCategory: false,
      unknownItemType: false,
      suggested: false
    }
  },
  watch: {
    device(newval) {
      console.log('Device changed', newval)
      this.updateCurrentDevice(newval)
    },
    suggestedCategory(newval) {
      if (newval) {
        // For add we always want to use this - we get called multiple times as they type.
        // For edit we probably don't, unless we have edited the category away.
        if (this.add || !this.currentDevice.category) {
          this.currentDevice.category = newval.idcategories

          // Make it obvious that we have done this to encourage people to review it rather than ignore it.
          this.suggested = true
          setTimeout(() => {
            this.suggested = false
          }, 5000)
        }
      } else {
        this.currentDevice.category = null
      }
    }
  },
  computed: {
    device() {
      return this.id ? this.$store.getters['devices/byId'](this.id) : null
    },
    itemTypes() {
      return this.$store.getters['items/list'];
    },
    idtouse() {
      return this.currentDevice ? this.currentDevice.id : null
    },
    disabled () {
      return !this.edit && !this.add
    },
    currentCategory () {
      return this.currentDevice ? this.currentDevice.category : null
    },
    currentType() {
      return this.currentDevice ? this.currentDevice.item_type : null
    },
    suggestedCategory() {
      let ret = null

      if (this.currentType) {
        // Some item types are the same as category names.
        this.clusters.forEach((cluster) => {
          cluster.categories.forEach((c) => {
            const name = this.__(c.name)
            if (Boolean(c.powered) === Boolean(this.powered) && !name.toLowerCase().localeCompare(this.currentType.toLowerCase())) {
              ret = {
                idcategories: c.idcategories,
                categoryname: c.name,
                powered: c.powered
              }
            }
          })
        })

        if (!ret) {
          // Now check the item types.  Stop at the first match, which is the most popular.
          this.itemTypes.every(t => {
            if (!ret && Boolean(t.powered) === Boolean(this.powered) && this.currentType.toLowerCase() == t.type.toLowerCase()) {
              ret = {
                idcategories: t.idcategories,
                categoryname: t.categoryname,
                powered: t.powered
              }

              return false
            }

            return true
          })
        }
      }

      return ret
    },
    showWeight () {
      // Powered devices don't allow editing of the weight except for the "None of the above" category, whereas
      // unpowered do.
      return !this.powered || (this.currentDevice && this.currentDevice.category === CATEGORY_MISC_POWERED)
    },
    suppressTypeWarning () {
      // We don't want to show the warning if we have not changed the type since it was last saved.
      return this.currentDevice && this.device && this.device.item_type === this.currentDevice.item_type
    },
    suppressBrandWarning () {
      // We don't want to show the warning if we have not changed the brand since it was last saved.
      return this.currentDevice && this.device && this.device.brand === this.currentDevice.brand
    },
    weightRequired() {
      // Weight is required (if shown) for misc (powered or unpowered).
      return this.currentDevice &&
          (this.powered && this.currentDevice.category === CATEGORY_MISC_POWERED ||
            !this.powered && this.currentDevice.category === CATEGORY_MISC_UNPOWERED)
    }
  },
  created () {
    // We take a copy of what's passed in so that we can then edit it in here before saving or cancelling.  We need
    this.currentDevice = {
      event_id: this.eventid,
      category: null,
      brand: null,
      model: null,
      age: null,
      repair_status: null,
      spare_parts: null,
      problem: null,
      assessment: null,
      quantity: 1,
    }

    if (this.device) {
      this.updateCurrentDevice(this.device)
    }

    if (this.add) {
      // Use a -ve id to give us something to track uploaded photos against.
      //
      // Need to ensure this isn't too large as the xref table has an int value.
      this.currentDevice.id = -Math.round(new Date().getTime() / 1000)
    }
  },
  methods: {
    updateCurrentDevice(device) {
      // Take a deep clone because we're messing with arrays.
      this.currentDevice = {...this.currentDevice, ...JSON.parse(JSON.stringify(device))}

      // Some values we need to munge back to the id for our selects.  This is a bit ugly because we have two lots
      // of field names, depending on which API we're using.
      if (typeof this.currentDevice.category === 'object') {
        if (this.currentDevice.category.idcategories) {
          this.currentDevice.category = this.currentDevice.category.idcategories
        } else {
          this.currentDevice.category = this.currentDevice.category.id
        }
      }

      this.currentDevice.estimate = parseFloat(this.currentDevice.estimate)
      this.currentDevice.age = parseFloat(this.currentDevice.age)

      this.partsProvider()
    },
    cancel () {
      this.$emit('close')
    },
    partsProvider () {
      // Third party parts are indicated via the parts provider field.
      return this.currentDevice.spare_parts ? this.currentDevice.spare_parts : null
    },
    async addDevice () {
      try {
        if (!this.currentDevice.category) {
          this.missingCategory = true
        } else {
          this.missingCategory = false

          // The API only creates a single device, so we loop on the client to create multiple.
          let toAdd = this.currentDevice
          toAdd = JSON.parse(JSON.stringify(toAdd))

          for (let i = 0; i < this.currentDevice.quantity; i++) {
            toAdd.id = this.currentDevice.id--
            await this.$store.dispatch('devices/add', toAdd)
          }

          console.log('Close')
          this.$emit('close')
        }
      } catch (e) {
        console.error('Edit failed', e)
        this.axiosError = e
      }
    },
    async saveDevice () {
      try {
        await this.$store.dispatch('devices/edit', this.currentDevice)
        this.$emit('close')
      } catch (e) {
        console.error('Edit failed', e)
        this.axiosError = e
      }
    },
    async removeImage (image) {
      await this.$store.dispatch('devices/deleteImage', {
        id: this.idtouse,
        idxref: image.idxref,
      })

      this.$store.dispatch('devices/fetch', this.id)
    },
    confirmDeleteDevice () {
      this.$refs.confirm.show()
    },
    deleteDevice () {
      this.$store.dispatch('devices/delete', this.id)
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

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

.segment {
  width: 100%;

  @include media-breakpoint-up(md) {
    width: 33%
  }
}

.br {
  border-right: 1px solid white;
}

.bl {
  border-left: 1px solid white;
}

.device-info {
  display: grid;
  grid-template-columns: repeat( auto-fit, minmax(350px, 1fr) );

  @include media-breakpoint-down(sm) {
    grid-template-columns: 100%;
  }
}

h3 {
  font-size: $font-size-base;
  font-weight: bold;
  color: $brand-light;
}

.add-device {
  background-color: $brand-light;

  .card {
    background-color: $brand-light;
    border-radius: 0;
  }

  h3 {
    color: #222;
  }

  ::v-deep {
    label {
      color: black;
      font-weight: bold;
    }
  }
}

.edit-device {
  background-color: $brand-grey;
  color: black;

  h3 {
    color: $brand-light;
  }

  .btn-tertiary {
    color: $brand-light;
    background-color: white;
    border: 1px solid $brand-light;
    box-shadow: 5px 5px 0 0 $brand-light;
  }

  .card {
    background-color: $brand-grey;
    border-radius: 0;
  }

  ::v-deep {
    label {
      color: black;
      font-weight: bold;
    }
  }
}

.botwhite {
  border-bottom: 1px solid white;
}

.border-thick {
  border: 3px solid red;
}

::v-deep .card .form-control:disabled {
  // Disabled is what happens for the view that people get if they can't edit the device.
  background-color: white;
}

::v-deep .form-text {
  line-height: 1rem;
}

::v-deep .suggested .multiselect {
  border: 3px solid #222 !important;
  width: calc(100% - 6px) !important;
}
</style>
