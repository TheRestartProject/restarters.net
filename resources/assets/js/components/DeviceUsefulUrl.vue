<template>
  <div>
    <div class="device-select-row mb-2 w-100">
      <b-input v-model="urlValue" :placeholder="translatedRepairURL" size="lg" class="marg" />
      <div>
        <b-btn variant="none" @click="deleteMe" class="p-0 ml-3 mt-2" v-if="notBlank">
          <b-img src="/icons/cross_ico.svg" class="icon" />
        </b-btn>
      </div>
    </div>
    <div class="device-select-row mb-2 w-100">
      <multiselect
          v-model="sourceValue"
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
    notBlank() {
      return this.urlValue || this.sourceValue
    },
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
        this.$emit('update:url', {
          source: this.sourceValue,
          url: newval
        })
      }
    },
    sourceValue: {
      get() {
        if (!this.url) {
          return null
        }

        return this.statusOptions.find(o => {
          return o.id === this.url.source
        })
      },
      set(newval) {
        this.$emit('update:url', {
          source: newval.id,
          url: this.urlValue
        })
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
  },
  methods: {
    deleteMe() {
      this.$emit('delete')
    }
  }
}
</script>
<style scoped lang="scss">
.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}

.icon {
  width: 25px;
}
</style>