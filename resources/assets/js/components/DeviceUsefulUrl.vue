<template>
  <div>
    <div class="device-select-row mb-2 w-100">
      <b-input :value="urlValue" :placeholder="translatedRepairURL" size="lg" class="marg" />
      <div />
    </div>
    <div class="device-select-row mb-2 w-100">
      <multiselect
          v-model="source"
          :placeholder="translatedRepairSource"
          :options="statusOptions"
          track-by="id"
          label="text"
          :multiple="false"
          :allow-empty="false"
          deselect-label=""
          :taggable="false"
          selectLabel=""
      />
      <div />
    </div>
  </div>
</template>
<script>
import { USEFUL_URL_SOURCE_MANUFACTURER, USEFUL_URL_SOURCE_THIRD_PARTY } from '../constants'

export default {
  props: {
    url: {
      type: Object,
      required: false,
      default: null
    }
  },
  data () {
    return {
      source: null
    }
  },
  computed: {
    statusOptions() {
      return [
        {
          id: USEFUL_URL_SOURCE_MANUFACTURER,
          text: this.translatedFromManufacturer
        },
        {
          id: USEFUL_URL_SOURCE_THIRD_PARTY,
          text: this.translatedFromThirdParty
        }
      ]
    },
    urlValue: {
      get() {
        return this.url ? this.url.url : null
      },
      set(newval) {
        this.$emit('update:url', newval)
      }
    },
    sourceValue: {
      get() {
        if (!this.url) {
          return null
        }

        return this.options.find(o => {
          return o.id === this.url.id
        })
      },
      set(newval) {
        this.$emit('update:source', newval.id)
      }
    },
    translatedRepairSource() {
      return this.$lang.get('devices.repair_source')
    },
    translatedRepairURL() {
      return this.$lang.get('devices.useful_repair_urls_explanation')
    },
    translatedFromThirdParty() {
      return this.$lang.get('devices.from_third_party')
    },
    translatedFromManufacturer() {
      return this.$lang.get('devices.from_manufacturer')
    }
  }
}
</script>
<style scoped lang="scss">
.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}
</style>