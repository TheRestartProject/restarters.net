<template>
  <b-form :class="{
      'edit-device': edit,
      'add-device': add
      }">
    <div class="device-info">
      <div class="br d-flex flex-column">
        <b-card no-body class="p-3 flex-grow-1 botwhite">
          <h3 class="mt-2 mb-4">{{ translatedTitleItems }}</h3>
          <DeviceCategorySelect class="mb-2" :category.sync="currentDevice.category" :clusters="clusters" :powered="powered" :icon-variant="add ? 'black' : 'brand'" />
          <div v-if="powered">
            <DeviceBrandSelect class="mb-2" :brand.sync="currentDevice.brand" :brands="brands" />
            <DeviceModel class="mb-2" :model.sync="currentDevice.model" :icon-variant="add ? 'black' : 'brand'" />
          </div>
          <DeviceType class="mb-2" :type.sync="currentDevice.item_type" :icon-variant="add ? 'black' : 'brand'" v-else />
          <DeviceWeight v-if="showWeight" :weight.sync="currentDevice.estimate" />
          <DeviceAge :age.sync="currentDevice.age" />
<!--          TODO Photos-->
        </b-card>
      </div>
      <div class="d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1">
          <h3 class="mt-2 mb-4">{{ translatedTitleRepair }}</h3>
          <DeviceRepairStatus :status.sync="currentDevice.repair_status" :steps.sync="currentDevice.repair_details" :parts.sync="currentDevice.spare_parts" :barriers.sync="currentDevice.barrier" :barrierList="barrierList" />
        </b-card>
      </div>
      <div class="bl d-flex flex-column botwhite">
        <b-card no-body class="p-3 flex-grow-1">
          <h3 class="mt-2 mb-4">{{ translatedTitleAssessment }}</h3>
          <DeviceProblem :problem.sync="currentDevice.problem" class="mb-4" />
          <DeviceNotes :notes.sync="currentDevice.notes" class="mb-4" />
          <DeviceUsefulUrls :device="device" :useful-urls.sync="currentDevice.usefulURLs" class="mb-2" />
          <div class="d-flex">
            <b-form-checkbox v-model="wiki" class="form-check form-check-large ml-4" :id="'wiki-' + (add ? '' : device.iddevices)" />
            <label :for="'wiki-' + (add ? '' : device.iddevices)">
              {{ translatedCaseStudy }}
            </label>
          </div>
        </b-card>
      </div>
    </div>
    <div class="d-flex justify-content-center flex-wrap pt-4 pb-4">
      <b-btn variant="primary" class="mr-2" v-if="add" @click="addDevice">
        {{ translatedAddDevice }}
      </b-btn>
      <b-btn variant="primary" class="mr-2" v-if="edit" @click="saveDevice">
        {{ translatedSave }}
      </b-btn>
      <DeviceQuantity v-if="add" :quantity.sync="currentDevice.quantity" class="flex-md-shrink-1 ml-2 mr-2" />
      <b-btn variant="tertiary" class="ml-2" @click="cancel">
        {{ translatedCancel }}
      </b-btn>
    </div>
  </b-form>




<!--  @if (!$add)-->
<!--  <label for="file" class="photolabel">@lang('devices.images')</label>-->
<!--  @endif-->
<!--  <div class="d-flex flex-wrap justify-content-left pt-3 photoform">-->
<!--    <div class="position-relative d-flex flex-wrap previews">-->
<!--      @if ($edit)-->
<!--      <form id="dropzoneEl-{{ $device->iddevices }}" data-deviceid="{{ $device->iddevices }}" class="dropzone dz-thumbnail dropzoneEl mr-1" action="/device/image-upload/{{ $device->iddevices }}" method="post" enctype="multipart/form-data" data-field1="" data-field2="">-->
<!--        @csrf-->
<!--        <div class="dz-default dz-message"></div>-->
<!--        <div class="fallback">-->
<!--          <input id="file-{{ $device->iddevices }}" name="file-{{ $device->iddevices }}" type="file" multiple />-->
<!--        </div>-->
<!--      </form>-->
<!--      @endif-->

<!--      @php( $images = $device->getImages() )-->
<!--      @if( count($images) > 0 )-->
<!--      @foreach($images as $image)-->
<!--      <div class="dz-preview ml-0 mr-1 p-0">-->
<!--        <div id="device-image-{{ $device->iddevices }}" class="dz-image">-->
<!--          <a href="/uploads/{{ $image->path }}" data-toggle="lightbox" class="">-->
<!--            <img src="/uploads/thumbnail_{{ $image->path }}" alt="placeholder" class="image-thumb"></a>-->
<!--        </div>-->
<!--        <a href="/device/image/delete/{{ $device->iddevices }}/{{{ $image->idimages }}}/{{{ $image->path }}}" data-device-id="{{ $device->iddevices }}" class="dz-remove ajax-delete-image">Remove file</a>-->
<!--      </div>-->
<!--      @endforeach-->
<!--      @endif-->
<!--      <div class="uploads-{{ $device->iddevices }}"></div>-->
</template>
<script>
import event from '../mixins/event'
import {
  FIXED,
  REPAIRABLE,
  END_OF_LIFE,
  SPARE_PARTS_MANUFACTURER,
  SPARE_PARTS_THIRD_PARTY,
  CATEGORY_MISC
} from '../constants'
import DeviceCategorySelect from './DeviceCategorySelect'
import DeviceBrandSelect from './DeviceBrandSelect'
import DeviceModel from './DeviceModel'
import DeviceWeight from './DeviceWeight'
import DeviceAge from './DeviceAge'
import DeviceType from './DeviceType'
import DeviceRepairStatus from './DeviceRepairStatus'
import DeviceProblem from './DeviceProblem'
import DeviceNotes from './DeviceNotes'
import DeviceUsefulUrls from './DeviceUsefulUrls'
import DeviceQuantity from './DeviceQuantity'

export default {
  components: {
    DeviceQuantity,
    DeviceUsefulUrls,
    DeviceNotes,
    DeviceProblem,
    DeviceRepairStatus,
    DeviceType, DeviceAge, DeviceWeight, DeviceModel, DeviceBrandSelect, DeviceCategorySelect},
  mixins: [ event ],
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
    powered: {
      // The server might return a number rather than a boolean.
      type: [ Boolean, Number ],
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentDevice: {},
    }
  },
  computed: {
    sparePartsNeeded() {
      return this.device.spare_parts === SPARE_PARTS_MANUFACTURER || this.device.spare_parts === SPARE_PARTS_THIRD_PARTY
    },
    showWeight() {
      // Powered devices don't allow editing of the weight except for the "None of the above" category, whereas
      // unpowered do.
      return !this.powered || (this.currentDevice && this.currentDevice.category === CATEGORY_MISC)
    },
    wiki: {
      // Need to convert server's number to/from a boolean.
      get() {
        return !!this.currentDevice.wiki
      },
      set(newval) {
        this.currentDevice.wiki = newval
      }
    },
    translatedTitleItems() {
      return this.$lang.get('devices.title_items')
    },
    translatedTitleRepair() {
      return this.$lang.get('devices.title_repair')
    },
    translatedTitleAssessment() {
      return this.$lang.get('devices.title_assessment')
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
    translatedCaseStudy() {
      return this.$lang.get('partials.solution_text2')
    },
    translatedSave() {
      return this.$lang.get('partials.save')
    },
    translatedAddDevice() {
      return this.$lang.get('partials.add_device')
    },
    translatedCancel() {
      return this.$lang.get('partials.cancel')
    },
  },
  mounted() {
    // We take a copy of what's passed in so that we can then edit it in here before saving or cancelling.  We need
    this.currentDevice = {
      event_id: this.idevents,
      category: null,
      brand: null,
      model: null,
      age: null,
      repair_status: null,
      spare_parts: null,
      problem: null,
      assessment: null,
      quantity: 1
    }

    if (this.device) {
      this.currentDevice = {...this.currentDevice, ...this.device}

      // Some values we need to munge back to the id for our selects.
      if (this.currentDevice.category) {
        this.currentDevice.category = this.currentDevice.category.idcategories
      }

      if (this.currentDevice.brand) {
        this.currentDevice.brand = this.brands.find(b => {
          return b.brand_name === this.currentDevice.brand
        }).id
      }
    }
  },
  methods: {
    cancel() {
      this.$emit('cancel')
    },
    async addDevice() {
      await this.$store.dispatch('devices/add', this.prepareDeviceForServer())
      this.$emit('cancel')
    },
    async saveDevice() {
      await this.$store.dispatch('devices/edit', this.prepareDeviceForServer())
      this.$emit('cancel')
    },
    prepareDeviceForServer() {
      // The device we send to the server is what is in currentDevice, with a couple of tweaks:
      // - The server takes the brand as a string rather than an id.
      // - The server only supports a single useful URL on add, via the url and source parameters
      // We map those here to keep the interface to the components neater.
      let device = this.currentDevice

      if (device.urls && device.urls.length) {
        device.url = device.urls[0].url
        device.source = device.urls[0].source
      }

      const selectedBrand = this.brands.find(b => {
        return b.id === device.brand
      })

      device.brand = selectedBrand ? selectedBrand.brand_name : null

      return device
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
  color: #fff;
}

.add-device {
  background-color: $brand-light;

  .card {
    background-color: $brand-light;
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
</style>