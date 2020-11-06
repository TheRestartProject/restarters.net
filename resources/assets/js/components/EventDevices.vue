<template>
  <CollapsibleSection class="lineheight" collapsed :count="deviceCount" always-show-count count-class="text-black font-weight-normal">
    <template slot="title">
      <div class="d-flex size">
        <b-img class="d-none d-md-block icon" src="/images/tv.svg" />&nbsp;{{ translatedTitle }}
      </div>
    </template>
    <template slot="content">
      <div class="d-none d-md-block">
        <b-tabs class="ourtabs ourtabs-brand w-100">
          <b-tab active title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedPowered }}</b> ({{ powered.length }})
                </div>
                <div class="d-flex">
                  <div class="mr-3 lower">
                    <b-img src="/images/trash_brand.svg" class="icon" />
                    {{ Math.round(stats.ewaste) }} kg
                  </div>
                  <div class="mr-1 lower">
                    <b-img src="/images/co2_brand.svg" class="icon" />
                    {{ Math.round(stats.co2) }} kg
                  </div>
                </div>
              </div>
            </template>
            <p v-html="translatedDescriptionPowered" />
            <EventDeviceList :devices="powered" :powered="true" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4" @click="addingPowered = true">
              <b-img class="icon mb-1" src="/images/add-icon.svg" /> {{ translatedAddPowered }}
            </b-btn>
            <EventDevice v-if="addingPowered" :powered="true" :add="true" :edit="false" :clusters="clusters" :idevents="idevents" :brands="brands" :barrier-list="barrierList" @close="addingPowered = false" />
          </b-tab>
          <b-tab title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedUnpowered }}</b> ({{ unpowered.length }})
                </div>
                <div class="lower">
                  <b-img src="/images/trash_brand.svg" class="icon" />
                  {{ Math.round(stats.unpowered_waste) }} kg
                </div>
              </div>
            </template>
            <p v-html="translatedDescriptionUnpowered" />
            <EventDeviceList :devices="unpowered" :powered="false" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4" @click="addingUnpowered = true">
              <b-img class="icon mb-1" src="/images/add-icon.svg" /> {{ translatedAddUnpowered }}
            </b-btn>
            <EventDevice v-if="addingUnpowered" :powered="false" :add="true" :edit="false" :clusters="clusters" :idevents="idevents" :event="event" :brands="brands" :barrier-list="barrierList" @close="addingUnpowered = false"/>
          </b-tab>
        </b-tabs>
      </div>
      <div class="d-block d-md-none">
        <CollapsibleSection class="lineheight" collapsed :count="powered.length" always-show-count count-class="text-black font-weight-normal small">
          <template slot="title">
            <div class="d-flex justify-content-between small ml-1 align-self-center">
              <div>
                <b>{{ translatedPowered }}</b>
              </div>
            </div>
          </template>
          <template slot="title-right">
            <div class="d-flex text-brand small">
              <div class="ml-2 mr-1 pt-1 lower align-self-center">
                <b-img src="/images/trash_brand.svg" class="icon" />
                {{ Math.round(stats.ewaste) }}
              </div>
              <div class="ml-1 mr-1 lower pt-1 small align-self-center">
                <b-img src="/images/co2_brand.svg" class="icon" />
                {{ Math.round(stats.co2) }}
              </div>
            </div>
          </template>
          <template slot="content">
            <p v-html="translatedDescriptionPowered" />
            <EventDeviceList :devices="powered" :powered="true" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4" @click="addingPowered = true">
              <b-img class="icon mb-1" src="/images/add-icon.svg" /> {{ translatedAddPowered }}
            </b-btn>
            <EventDevice v-if="addingPowered" :powered="true" :add="true" :edit="false" :clusters="clusters" :idevents="idevents" :brands="brands" :barrier-list="barrierList" @close="addingPowered = false" />
          </template>
        </CollapsibleSection>
        <CollapsibleSection class="lineheight" collapsed :count="unpowered.length" always-show-count count-class="text-black font-weight-normal small">
          <template slot="title">
            <div class="d-flex justify-content-between small ml-1 align-self-center">
              <div>
                <b>{{ translatedUnpowered }}</b>
              </div>
            </div>
          </template>
          <template slot="title-right">
            <div class="d-flex text-brand small">
              <div class="ml-2 mr-1 pt-1 lower align-self-center">
                <b-img src="/images/trash_brand.svg" class="icon" />
                {{ Math.round(stats.unpowered_waste) }}
              </div>
            </div>
          </template>
          <template slot="content">
            <p v-html="translatedDescriptionUnpowered" />
            <EventDeviceList :devices="unpowered" :powered="false" :canedit="canedit" :idevents="idevents" :brands="brands" :barrier-list="barrierList" :clusters="clusters" />
            <b-btn variant="primary" v-if="canedit" class="mb-4 ml-4" @click="addingUnpowered = true">
              <b-img class="icon mb-1" src="/images/add-icon.svg" /> {{ translatedAddUnpowered }}
            </b-btn>
            <EventDevice v-if="addingUnpowered" :powered="false" :add="true" :edit="false" :clusters="clusters" :idevents="idevents" :brands="brands" :barrier-list="barrierList" @close="addingUnpowered = false" />
          </template>
        </CollapsibleSection>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import event from '../mixins/event'
import ExternalLink from './ExternalLink'
import CollapsibleSection from './CollapsibleSection'
import EventDeviceList from './EventDeviceList'
import EventDeviceSummary from './EventDeviceSummary'
import EventDevice from './EventDevice'

export default {
  components: {EventDevice, EventDeviceSummary, EventDeviceList, CollapsibleSection, ExternalLink},
  mixins: [ event ],
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
      return this.$store.getters['devices/byEvent'](this.idevents) || []
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
    translatedTitle() {
      return this.$lang.get('devices.title_items_at_event')
    },
    translatedPowered() {
      return this.$lang.get('devices.title_powered')
    },
    translatedUnpowered() {
      return this.$lang.get('devices.title_unpowered')
    },
    translatedDescriptionPowered() {
      return this.$lang.get('devices.description_powered')
    },
    translatedDescriptionUnpowered() {
      return this.$lang.get('devices.description_unpowered')
    },
    translatedAddPowered() {
      return this.$lang.get('partials.add_device_powered')
    },
    translatedAddUnpowered() {
      return this.$lang.get('partials.add_device_unpowered')
    }
  },
  created() {
    // The devices are passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    this.$store.dispatch('devices/set', {
      idevents: this.idevents,
      devices: this.devices
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

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

.size {
  font-size: 28px;
}
</style>