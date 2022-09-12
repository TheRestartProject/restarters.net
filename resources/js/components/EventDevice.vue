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
                      :icon-variant="add ? 'black' : 'brand'" :item-types="itemTypes" :disabled="disabled"
                      :suppress-type-warning="suppressTypeWarning" :powered="powered"
                      :unknown.sync="unknownItemType"
          />
          <DeviceCategorySelect :class="{
            'mb-2': true,
            'border-thick': missingCategory,
            'pulsate': pulsating,
            }" :category.sync="currentDevice.category" :clusters="clusters" :powered="powered"
                                :icon-variant="add ? 'black' : 'brand'" :disabled="disabled" @changed="categoryChange"/>

          <DeviceBrand class="mb-2" :brand.sync="currentDevice.brand" :brands="brands" :disabled="disabled"
                       :suppress-brand-warning="suppressBrandWarning"/>
          <DeviceModel class="mb-2" :model.sync="currentDevice.model" :icon-variant="add ? 'black' : 'brand'"
                       :disabled="disabled"/>
          <DeviceWeight v-if="showWeight" :weight.sync="currentDevice.estimate" :disabled="disabled"/>
          <DeviceAge :age.sync="currentDevice.age" :disabled="disabled"/>
          <DeviceImages :idevents="idevents" :device="currentDevice" :add="add" :edit="edit" :disabled="disabled"
                        class="mt-2" @remove="removeImage($event)"/>
        </b-card>
      </div>
      <div class="d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1 border-0">
          <h3 class="mt-2 mb-4">{{ __('devices.title_repair') }}</h3>
          <DeviceRepairStatus :status.sync="currentDevice.repair_status" :steps.sync="currentDevice.repair_details"
                              :parts.sync="currentDevice.spare_parts" :barriers.sync="currentDevice.barrier"
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
          <DeviceUsefulUrls :device="device" :urls.sync="currentDevice.urls" class="mb-2" :disabled="disabled"/>
          <div class="d-flex">
            <b-form-checkbox v-model="wiki" class="form-check form-check-large ml-4"
                             :id="'wiki-' + (add ? '' : device.iddevices)" :disabled="disabled"/>
            <label :for="'wiki-' + (add ? '' : device.iddevices)">
              {{ __('partials.solution_text2') }}
            </label>
          </div>
        </b-card>
      </div>
    </div>
    <b-alert :show="missingCategory" variant="danger">
      <p>{{ __('events.form_error') }}</p>
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
      <b-btn variant="tertiary" class="ml-2" @click="cancel" v-if="cancelButton">
        {{ __('partials.cancel') }}
      </b-btn>
    </div>
    <ConfirmModal @confirm="deleteDevice" ref="confirm"/>
  </b-form>
</template>
<script>
import event from '../mixins/event'
import {
  FIXED,
  REPAIRABLE,
  END_OF_LIFE,
  SPARE_PARTS_MANUFACTURER,
  SPARE_PARTS_THIRD_PARTY,
  CATEGORY_MISC, NEXT_STEPS_DIY, NEXT_STEPS_PROFESSIONAL, NEXT_STEPS_MORE_TIME,
  PARTS_PROVIDER_MANUFACTURER,
  PARTS_PROVIDER_THIRD_PARTY, SPARE_PARTS_NOT_NEEDED
} from '../constants'
import DeviceCategorySelect from './DeviceCategorySelect'
import DeviceBrand from './DeviceBrand'
import DeviceModel from './DeviceModel'
import DeviceWeight from './DeviceWeight'
import DeviceAge from './DeviceAge'
import DeviceType from './DeviceType'
import DeviceRepairStatus from './DeviceRepairStatus'
import DeviceProblem from './DeviceProblem'
import DeviceNotes from './DeviceNotes'
import DeviceUsefulUrls from './DeviceUsefulUrls'
import DeviceQuantity from './DeviceQuantity'
import FileUploader from './FileUploader'
import DeviceImages from './DeviceImages'
import ConfirmModal from './ConfirmModal'

export default {
  components: {
    ConfirmModal,
    DeviceImages,
    FileUploader,
    DeviceQuantity,
    DeviceUsefulUrls,
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
    device: {
      type: Object,
      required: false,
      default: null
    },
    idevents: {
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
    itemTypes: {
      type: Array,
      required: false,
      default: null
    },
  },
  data () {
    return {
      currentDevice: {},
      missingCategory: false,
      unknownItemType: false,
      pulsating: false
    }
  },
  watch: {
    currentCategory (newval) {
      if (this.missingCategory && newval) {
        // Reset warning.
        this.missingCategory = false
      }
    },
    suggestedCategory(newval) {
      if (newval) {
        this.currentDevice.category = newval.idcategories

        // Make it obvious that we have done this to encourage people to review it rather than ignore it.
        this.pulsating = true
        setTimeout(() => {
          this.pulsating = false
        }, 5000)
      }
    }
  },
  computed: {
    disabled () {
      return !this.edit && !this.add
    },
    currentCategory () {
      return this.currentDevice ? this.currentDevice.category : null
    },
    suggestedCategory() {
      let ret = null

      if (this.currentDevice && this.currentDevice.item_type) {
        // Some item types are the same as category names.
        this.clusters.forEach((cluster) => {
          cluster.categories.forEach((c) => {
            const name = this.$lang.get('strings.' + c.name)

            if (Boolean(c.powered) === Boolean(this.powered) && name.toLowerCase().indexOf(this.currentDevice.item_type.toLowerCase()) !== -1) {
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
            if (!ret && Boolean(t.powered) === Boolean(this.powered) && this.currentDevice.item_type === t.item_type) {
              ret = t

              return false
            }

            return true
          })
        }
      }

      return ret
    },
    suggestedCategoryId() {
      return this.suggestedCategory ? this.suggestedCategory.idcategories : null
    },
    suggestedCategoryName() {
      return this.suggestedCategory? this.suggestedCategory.categoryname : null
    },
    computedPowered() {
      if (this.suggestedCategory) {
        if (this.suggestedCategory.powered) {
          return 'Powered'
        } else {
          return 'Unpowered'
        }
      } else {
        return null
      }
    },
    aggregate () {
      if (!this.currentCategory) {
        return false
      }

      if (this.powered && this.currentCategory === CATEGORY_MISC) {
        return true
      }

      let ret = false

      this.clusters.forEach((cluster) => {
        let categories = []

        cluster.categories.forEach((c) => {
          if (this.currentCategory === c.idcategories) {
            ret = c.aggregate
          }
        })
      })

      return ret
    },
    showWeight () {
      // Powered devices don't allow editing of the weight except for the "None of the above" category, whereas
      // unpowered do.
      return !this.powered || (this.currentDevice && this.currentDevice.category === CATEGORY_MISC)
    },
    wiki: {
      // Need to convert server's number to/from a boolean.
      get () {
        return !!this.currentDevice.wiki
      },
      set (newval) {
        this.currentDevice.wiki = newval
      }
    },
    suppressTypeWarning () {
      // We don't want to show the warning if we have not changed the type since it was last saved.
      return this.currentDevice && this.device && this.device.item_type === this.currentDevice.item_type
    },
    suppressBrandWarning () {
      // We don't want to show the warning if we have not changed the brand since it was last saved.
      return this.currentDevice && this.device && this.device.brand === this.currentDevice.brand
    },
  },
  created () {
    // We take a copy of what's passed in so that we can then edit it in here before saving or cancelling.  We need
    this.currentDevice = {
      event_id: this.idevents,
      category: null,
      brand: null,
      model: null,
      age: null,
      repair_details: null,
      repair_status: null,
      spare_parts: null,
      problem: null,
      assessment: null,
      quantity: 1,
      urls: []
    }

    if (this.device) {
      // Take a deep clone because we're messing with arrays.
      this.currentDevice = {...this.currentDevice, ...JSON.parse(JSON.stringify(this.device))}

      // Some values we need to munge back to the id for our selects.
      if (this.currentDevice.category) {
        this.currentDevice.category = this.currentDevice.category.idcategories
      }

      this.currentDevice.estimate = parseFloat(this.currentDevice.estimate)
      this.currentDevice.age = parseFloat(this.currentDevice.age)

      this.nextSteps()
      this.partsProvider()
    }
  },
  methods: {
    cancel () {
      this.$emit('close')
    },
    nextSteps () {
      // The next step value is held in multiple properties of the object.
      if (this.currentDevice.do_it_yourself) {
        this.currentDevice.repair_details = NEXT_STEPS_DIY
      } else if (this.currentDevice.professional_help) {
        this.currentDevice.repair_details = NEXT_STEPS_PROFESSIONAL
      } else if (this.currentDevice.more_time_needed) {
        this.currentDevice.repair_details = NEXT_STEPS_MORE_TIME
      } else {
        this.currentDevice.repair_details = null
      }
    },
    partsProvider () {
      // Third part parts are indicated via the parts provider field.
      if (this.currentDevice.spare_parts === SPARE_PARTS_NOT_NEEDED) {
        this.currentDevice.spare_parts = SPARE_PARTS_NOT_NEEDED
      } else if (this.currentDevice.parts_provider === PARTS_PROVIDER_THIRD_PARTY) {
        this.currentDevice.spare_parts = SPARE_PARTS_THIRD_PARTY
      } else {
        this.currentDevice.spare_parts = SPARE_PARTS_MANUFACTURER
      }
    },
    async addDevice () {
      if (!this.currentDevice.category) {
        this.missingCategory = true
      } else {
        this.missingCategory = false

        const createdDevices = await this.$store.dispatch('devices/add', this.prepareDeviceForServer())

        if (this.currentDevice.urls) {
          // We have some useful URLs.  Apply them to each of the created devices.
          createdDevices.forEach(async (d) => {
            this.currentDevice.urls.forEach(async (u) => {
              await this.$store.dispatch('devices/addURL', {
                iddevices: d.iddevices,
                url: u
              })
            })
          })
        }

        this.$emit('close')
      }
    },
    async saveDevice () {
      await this.$store.dispatch('devices/edit', this.prepareDeviceForServer())

      // We need to update the useful URLs, which might have been added/edited/deleted from what we originally had.
      this.currentDevice.urls.forEach(async (u) => {
        if (!u.id) {
          // This has no id, and hence is a new useful URL added in this edit.  Create it.
          await this.$store.dispatch('devices/addURL', {
            iddevices: this.device.iddevices,
            url: u
          })
        } else {
          // This has an id, and therefore already existed on the server.
          const existing = this.device.urls.find(u2 => {
            return u2.id === u.id
          })

          if (existing.url !== u.url || existing.source !== u.source) {
            await this.$store.dispatch('devices/editURL', {
              iddevices: this.device.iddevices,
              url: u
            })
          }
        }
      })

      // Now find any URLs which were present originally but are no longer present - these need to be deleted.
      if (this.device.urls) {
        this.device.urls.forEach(async (u) => {
          const present = this.currentDevice.urls.find(u2 => {
            return u2.id === u.id
          })

          if (!present) {
            await this.$store.dispatch('devices/deleteURL', {
              iddevices: this.device.iddevices,
              url: u
            })
          }
        })
      }

      this.$emit('close')
    },
    prepareDeviceForServer () {
      // The device we send to the server is what is in currentDevice, with a couple of tweaks:
      // - The server takes the brand as a string rather than an id.
      // - The server only supports a single useful URL on add, via the url and source parameters
      // We map those here to keep the interface to the components neater.
      let device = this.currentDevice

      if (device.urls && device.urls.length) {
        device.url = device.urls[0].url
        device.source = device.urls[0].source
      }

      return device
    },
    removeImage (image) {
      // TODO LATER The remove of the image should not happen until the edit completes.  At the moment we do it
      // immediately.  The way we set ids here is poor, but this is because the underlying API call for images
      // is weak.
      image.iddevices = this.currentDevice.iddevices
      this.$store.dispatch('devices/deleteImage', image)
    },
    confirmDeleteDevice () {
      this.$refs.confirm.show()
    },
    deleteDevice () {
      this.$store.dispatch('devices/delete', {
        iddevices: this.device.iddevices,
        idevents: this.idevents
      })

      window.location = '/fixometer'
    },
    categoryChange () {
      // Any item type we might have is no longer valid.
      this.currentDevice.item_type = null
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

  .useful-repair-urls .input-group .form-control {
    border-radius: initial;
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

  ::v-deep {
    label {
      color: white;
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

/deep/ .card .form-control:disabled {
  // Disabled is what happens for the view that people get if they can't edit the device.
  background-color: white;
}

/deep/ .form-text {
  line-height: 1rem;
}

.pulsate {
  -webkit-animation: pulsate 1s ease-out;
  -webkit-animation-iteration-count: infinite;
}
</style>
