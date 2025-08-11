<template>
  <CollapsibleSection class="lineheight" collapsed :count="deviceCount" always-show-count count-class="text-black font-weight-normal">
    <template slot="title">
      <div class="d-flex">
        <b-img class="d-none d-md-block icon" :src="imageUrl('/images/tv.svg')" />&nbsp;{{ __('devices.title_items_at_event') }}
      </div>
    </template>
    <template slot="content">
      <div class="d-none d-md-block">
        <b-tabs class="ourtabs ourtabs-brand w-100">
          <b-tab active title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ __('devices.title_powered') }}</b> ({{ powered.length }})
                </div>
                <div class="d-flex">
                  <div class="mr-3 lower">
                    <b-img :src="imageUrl('/images/trash_brand.svg')" class="icon" />
                    {{ Math.round(stats.waste_powered) }} kg
                  </div>
                  <div class="mr-1 lower">
                    <b-img :src="imageUrl('/images/co2_brand.svg')" class="icon" />
                    {{ Math.round(stats.co2_powered) }} kg
                  </div>
                </div>
              </div>
            </template>
            <p v-html="__('devices.description_powered')" />
            <EventDeviceList :devices="powered" :powered="true" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4 add-powered-device-desktop" @click="addPowered($event)">
              <b-img class="icon mb-1" :src="imageUrl('/images/add-icon.svg')" /> {{ __('partials.add_device_powered') }}
            </b-btn>
            <EventDevice v-if="addingPowered" :powered="true" :add="true" :edit="false" :clusters="clusters" :eventid="idevents" :brands="brands" :barrier-list="barrierList" @close="closePowered" />
          </b-tab>
          <b-tab title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ __('devices.title_unpowered') }}</b> ({{ unpowered.length }})
                </div>
                <div class="d-flex">
                  <div class="mr-3 lower">
                    <b-img :src="imageUrl('/images/trash_brand.svg')" class="icon" />
                    {{ Math.round(stats.waste_unpowered) }} kg
                  </div>
                  <div class="mr-1 lower">
                    <b-img :src="imageUrl('/images/co2_brand.svg')" class="icon" />
                    {{ Math.round(stats.co2_unpowered) }} kg
                  </div>
                </div>

              </div>
            </template>
            <p v-html="__('devices.description_unpowered')" />
            <EventDeviceList :devices="unpowered" :powered="false" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4 add-unpowered-device-desktop" @click="addUnpowered($event)">
              <b-img class="icon mb-1" :src="imageUrl('/images/add-icon.svg')" /> {{ __('partials.add_device_unpowered') }}
            </b-btn>
            <EventDevice v-if="addingUnpowered" :powered="false" :add="true" :edit="false" :clusters="clusters" :eventid="idevents" :event="event" :brands="brands" :barrier-list="barrierList" @close="closeUnpowered"/>
          </b-tab>
        </b-tabs>
      </div>
      <div class="d-block d-md-none">
        <CollapsibleSection class="lineheight" collapsed :count="powered.length" always-show-count count-class="text-black font-weight-normal small">
          <template slot="title">
            <div class="d-flex justify-content-between small ml-1 align-self-center">
              <div>
                <b>{{ __('devices.title_powered') }}</b>
              </div>
            </div>
          </template>
          <template slot="title-right">
            <div class="d-flex text-brand small text-center">
              <div class="ml-2 mr-1 pt-1 lower small">
                <b-img :src="imageUrl('/images/trash_brand.svg')" class="icon" />
                {{ Math.round(stats.waste_powered) }}
              </div>
              <div class="ml-1 mr-1 lower pt-1 small">
                <b-img :src="imageUrl('/images/co2_brand.svg')" class="icon" />
                {{ Math.round(stats.co2_powered) }}
              </div>
            </div>
          </template>
          <template slot="content">
            <p v-html="__('devices.description_powered')" />
            <EventDeviceList :devices="powered" :powered="true" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4 add-powered-device-mobile" @click="addPowered($event)">
              <b-img class="icon mb-1" :src="imageUrl('/images/add-icon.svg')" /> {{ __('partials.add_device_powered') }}
            </b-btn>
            <EventDevice v-if="addingPowered" :powered="true" :add="true" :edit="false" :clusters="clusters" :eventid="idevents" :brands="brands" :barrier-list="barrierList" @close="addingPowered = false" />
          </template>
        </CollapsibleSection>
        <CollapsibleSection class="lineheight" collapsed :count="unpowered.length" always-show-count count-class="text-black font-weight-normal small">
          <template slot="title">
            <div class="d-flex justify-content-between small ml-1 align-self-center">
              <div>
                <b>{{ __('devices.title_unpowered') }}</b>
              </div>
            </div>
          </template>
          <template slot="title-right">
            <div class="d-flex text-brand small text-center">
              <div class="ml-2 mr-1 pt-1 lower small">
                <b-img :src="imageUrl('/images/trash_brand.svg')" class="icon" />
                {{ Math.round(stats.waste_unpowered) }}
              </div>
              <div class="ml-1 mr-1 lower pt-1 small">
                <b-img :src="imageUrl('/images/co2_brand.svg')" class="icon" />
                {{ Math.round(stats.co2_unpowered) }}
              </div>
            </div>
          </template>
          <template slot="content">
            <p v-html="__('devices.description_unpowered')" />
            <EventDeviceList :devices="unpowered" :powered="false" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4 add-unpowered-device-desktop" @click="addUnpowered($event)">
              <b-img class="icon mb-1" :src="imageUrl('/images/add-icon.svg')" /> {{ __('partials.add_device_unpowered') }}
            </b-btn>
            <EventDevice v-if="addingUnpowered" :powered="false" :add="true" :edit="false" :clusters="clusters" :eventid="idevents" :brands="brands" :barrier-list="barrierList" @close="addingUnpowered = false" />
          </template>
        </CollapsibleSection>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import event from '../mixins/event'
import images from '../mixins/images'
import ExternalLink from './ExternalLink.vue'
import CollapsibleSection from './CollapsibleSection.vue'
import EventDeviceList from './EventDeviceList.vue'
import EventDeviceSummary from './EventDeviceSummary.vue'
import EventDevice from './EventDevice.vue'

export default {
  components: {EventDevice, EventDeviceSummary, EventDeviceList, CollapsibleSection, ExternalLink},
  mixins: [ event, images ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    devices: {
      type: Array,
      required: false,
      default: null
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
      addingPowered: false,
      addingUnpowered: false,
    }
  },
  computed: {
    stats() {
      return this.$store.getters['events/getStats'](this.idevents)
    },
    allDevices() {
      console.log('All devices', this.$store.getters['devices/byEvent'](this.idevents) || [])
      const deviceIds = this.$store.getters['devices/byEvent'](this.idevents) || []
      const devices = []

      deviceIds.forEach((id) => {
        const device = this.$store.getters['devices/byId'](id)
        if (device) {
          devices.push(device)
        }
      })

      return devices
    },
    powered() {
      return this.allDevices.filter((d) => {
        return d.category.powered
      })
    },
    unpowered() {
      return this.allDevices.filter((d) => {
        return !d.category.powered
      })
    },
    deviceCount() {
      return this.powered.length + this.unpowered.length
    },
  },
  created() {
    // The devices are passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    this.$store.dispatch('devices/setForEvent', {
      eventid: this.idevents,
      devices: this.devices
    })
  },
  methods: {
    async addPowered(event) {
      this.addingPowered = true
      await this.$nextTick()
      event.target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      })
    },
    async addUnpowered(event) {
      this.addingUnpowered = true
      await this.$nextTick()
      event.target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      })
    },
    closePowered() {
      this.addingPowered = false
    },
    closeUnpowered() {
      this.addingUnpowered = false
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.lineheight {
  line-height: 2;
}

.readmore {
  white-space: pre-wrap !important;
}

.icon {
  width: 20px;
  margin-bottom: 3px;
}

.lower {
  text-transform: lowercase;
}
</style>