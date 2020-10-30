<template>
  <div>
    <DeviceUsefulUrl v-for="(url, ix) in currentURLs" :key="'useful-' + ix" :url="url" @update:url="update(ix, $event)" class="mb-2" @delete="remove(url)" />
  </div>
</template>
<script>
import Vue from 'vue'
import DeviceUsefulUrl from './DeviceUsefulUrl'

export default {
  components: {DeviceUsefulUrl},
  props: {
    urls: {
      type: Array,
      required: false,
      default: null
    }
  },
  data () {
    return {
      currentURLs: []
    }
  },
  created() {
    // Take copy of the input URLs for manipulation.
    this.currentURLs = this.urls
    this.addBlank()
  },
  watch: {
    currentURLs(newval) {
      // When the set of URLs changes, we want to tell the parent.
      this.emitURLs()
      this.addBlank()
    }
  },
  methods: {
    addBlank() {
      let gotblank = this.currentURLs.find(u => {
        return !u.url && !u.source
      })

      if (!gotblank) {
        // Always want to have an empty one.
        this.currentURLs.push({
          id: null,
          url: null,
          source: null
        })
      }
    },
    update(ix, url) {
      Vue.set(this.currentURLs, ix, url)
    },
    remove(url) {
      this.currentURLs = this.currentURLs.filter(u => {
        return u !== url
      })
    },
    emitURLs() {
      // Filter out any blank ones.
      const urls = this.currentURLs.filter(u => {
        return u.url || u.source
      })

      this.$emit('update:urls', urls)
    }
  }
}
</script>