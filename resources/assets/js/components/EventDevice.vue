<template>
  <b-form :class="{
      'edit-device': edit,
      'add-device': add
      }">
    <div class="device-info">
      <div class="br">
        <b-card no-body class="p-3">
          <h3 class="mt-2 mb-4">{{ translatedTitleItems }}</h3>
          <DeviceCategorySelect class="mb-2" :category.sync="category" :clusters="clusters" :powered="powered" :icon-variant="add ? 'black' : 'brand'" />
          <div v-if="powered">
            <DeviceBrandSelect class="mb-2" :brand.sync="brand" :brands="brands" />
            <DeviceModel class="mb-2" :model.sync="model" />
          </div>
          <DeviceType class="mb-2" :type.sync="type" v-else />
          <DeviceWeight v-if="showWeight" :estimate.sync="estimate" />
          <DeviceAge :age.sync="age" />
        </b-card>
      </div>
      <div>
        <b-card no-body class="p-3">
          <h3 class="mt-2 mb-4">{{ translatedTitleRepair }}</h3>
          <DeviceRepairStatus :status.sync="status" :steps.sync="steps" :parts.sync="parts" :barriers.sync="barriers" :barrierList="barrierList" />
        </b-card>
      </div>
      <div class="bl" />
    </div>
  </b-form>



<!--            <div class="form-control form-control__select form-control__select_placeholder mb-2 col-device {{ $device->repair_status == 3 ? '' : 'd-none' }}">-->
<!--              <select class="select2 select2-repair-barrier repair-barrier" name="barrier[]" multiple>-->
<!--                @foreach( FixometerHelper::allBarriers() as $barrier )-->
<!--                <option value="{{{ $barrier->id }}}" @if ( $device->barriers->contains($barrier->id) ) selected @endif>@lang($barrier->barrier)</option>-->
<!--                @endforeach-->
<!--              </select>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->

<!--      <div class="card {{ $edit ? 'card-event-edit-item' :  'card-event-add-item' }} flex-grow-1 border border-top-0 border-bottom-1 border-left-0 border-right-0 border-white">-->
<!--        <div class="card-body">-->
<!--          <h3>@lang('devices.title_assessment')</h3>-->
<!--          <div class="mt-4">-->
<!--            <div class="mb-2 device-select-row">-->
<!--              <div class="form-group">-->
<!--                <textarea class="form-control" rows="6" name="problem" placeholder="@lang('partials.description_of_problem_solution')">{!! $device->problem !!}</textarea>-->
<!--              </div>-->
<!--              <div data-toggle="popover" data-placement="left" data-html="true" data-content="@lang('devices.tooltip_problem')"  class="ml-3 mt-2">-->
<!--                @if ($add)-->
<!--                <img class="icon clickable" src="/icons/info_ico_black.svg">-->
<!--                @elseif ($edit)-->
<!--                <img class="icon clickable" src="/icons/info_ico_green.svg">-->
<!--                @endif-->
<!--              </div>-->
<!--            </div>-->

<!--            <div class="mb-2 device-select-row">-->
<!--              <div class="form-group">-->
<!--                <textarea class="form-control" rows="6" name="notes" placeholder="@lang('devices.placeholder_notes')">{!! $device->notes !!}</textarea>-->
<!--              </div>-->
<!--              <div data-toggle="popover" data-placement="left" data-html="true" data-content="@lang('devices.tooltip_notes')"  class="ml-3 mt-2">-->
<!--                @if ($add)-->
<!--                <img class="icon clickable" src="/icons/info_ico_black.svg">-->
<!--                @elseif ($edit)-->
<!--                <img class="icon clickable" src="/icons/info_ico_green.svg">-->
<!--                @endif-->
<!--              </div>-->
<!--            </div>-->

<!--            @include('partials.useful-repair-urls-add-or-edit', ['urls' => $device->urls, 'device' => $device, 'editable' => $add || $edit])-->

<!--            <div class="form-check d-flex align-items-center justify-content-start">-->
<!--              <input class="form-check-input form-check-large" type="checkbox" id="wiki-{{ $device->iddevices }}" name="wiki" value="1" @if( $device->wiki == 1 ) checked @endif>-->
<!--              <label class="form-check-label" for="wiki-{{ $device->iddevices }}">@lang('partials.solution_text2')</label>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->
<!--    <div class="d-flex justify-content-center flex-wrap {{ $edit ? 'card-event-edit-item' :  'card-event-add-item' }} pt-4 pb-4">-->
<!--      @if ($add || $edit)-->
<!--      @if ($edit)-->
<!--      <button type="submit" class="btn btn-primary btn-save2 mr-2">@lang('partials.save')</button>-->
<!--      @else-->
<!--      <button type="submit" class="btn btn-primary btn-save2 mr-2">@lang('partials.add_device')</button>-->

<!--      {{&#45;&#45; We only have the quantity when adding. &#45;&#45;}}-->
<!--      <div class="form-control form-control__select flex-md-shrink-1 ml-4 mr-4" style="width: 70px;">-->
<!--        <select name="quantity" class="quantity select2">-->
<!--          <option selected value="1">1</option>-->
<!--          <option value="2">2</option>-->
<!--          <option value="3">3</option>-->
<!--          <option value="4">4</option>-->
<!--          <option value="5">5</option>-->
<!--          <option value="6">6</option>-->
<!--          <option value="7">7</option>-->
<!--          <option value="8">8</option>-->
<!--          <option value="9">9</option>-->
<!--          <option value="10">10</option>-->
<!--        </select>-->
<!--      </div>-->
<!--      @endif-->
<!--      @endif-->
<!--      <a class="collapsed ml-2" data-toggle="collapse" href="#add-edit-device-{{ $powered ? 'powered' : 'unpowered' }}-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="add-device">-->
<!--        <button class="btn btn-tertiary" type="button">@lang('partials.cancel')</button>-->
<!--      </a>-->
<!--    </div>-->
<!--  </b-form>-->
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
// TODO Edit / delete
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

export default {
  components: {
    DeviceRepairStatus,
    DeviceType, DeviceAge, DeviceWeight, DeviceModel, DeviceBrandSelect, DeviceCategorySelect},
  mixins: [ event ],
  props: {
    device: {
      type: Object,
      required: false,
      default: null
    },
    eventId: {
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
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      category: null,
      brand: null,
      model: null,
      type: null,
      estimate: null,
      age: null,
      status: null,
      parts: null,
      steps: null,
      barriers: null
    }
  },
  computed: {
    sparePartsNeeded() {
      return this.device.spare_parts === SPARE_PARTS_MANUFACTURER || this.device.spare_parts === SPARE_PARTS_THIRD_PARTY
    },
    showWeight() {
      // Powered devices don't allow editing of the weight except for the "None of the above" category, whereas
      // unpowered do.
      return (this.device && !this.device.powered) || this.category === CATEGORY_MISC
    },
    translatedTitleItems() {
      return this.$lang.get('devices.title_items')
    },
    translatedTitleRepair() {
      return this.$lang.get('devices.title_repair')
    },
    translatedCategory() {
      return this.$lang.get('devices.category')
    },
  },
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
  grid-template-columns: repeat( auto-fit, minmax(360px, 1fr) );

  .useful-repair-urls .input-group .form-control {
    border-radius: initial;
  }
}

h3 {
  font-size: 0.9rem;
  color: #fff;
  font-weight: bold;
}

.edit-device {

}

.add-device {
  background-color: $brand-light;

  .card {
    background-color: $brand-light;
  }
}
</style>