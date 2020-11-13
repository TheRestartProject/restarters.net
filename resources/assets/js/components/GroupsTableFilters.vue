<template>
  <div class="layout">
    <b-form-input
        v-model="searchName"
        type="search"
        :placeholder="translatedSearchNamePlaceholder"
        class="mb-1 mb-md-0"
    />
    <div />
    <multiselect
        v-model="searchTags"
        :placeholder="translatedSearchTagsPlaceholder"
        :options="allGroupTags"
        track-by="id"
        label="tag_name"
        :multiple="true"
        :allow-empty="false"
        deselect-label=""
        :taggable="true"
        selectLabel=""
        class="m-0 mb-1 mb-md-0"
        allow-empty
    />
    <div />
    <b-form-input
        v-model="searchLocation"
        type="search"
        :placeholder="translatedSearchLocationPlaceholder"
        class="mb-1 mb-md-0"
    />
    <div />
    <multiselect
        v-model="searchCountry"
        :placeholder="translatedCountries"
        :options="countryOptions"
        track-by="country"
        label="country"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        class="m-0 mb-1 mb-md-0"
        allow-empty
    />
    <div />
    <multiselect
        v-model="searchNetwork"
        :placeholder="translatedNetworks"
        :options="networkOptions"
        track-by="id"
        label="name"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        class="m-0 mb-1 mb-md-0"
        allow-empty
    />
  </div>
</template>
<script>
export default {
  props: {
    groups: {
      type: Array,
      required: true
    },
    network: {
      type: Object,
      required: false,
      default: null
    },
    networks: {
      type: Array,
      required: false,
      default: null
    },
    allGroupTags: {
      type: Array,
      required: false,
      default: null
    }
  },
  data () {
    return {
      searchName: null,
      searchLocation: null,
      searchNetwork: null,
      searchCountry: null,
      searchTags: null
    }
  },
  watch: {
    searchName(newVal) {
      this.$emit('update:name', newVal)
    },
    searchLocation(newVal) {
      this.$emit('update:location', newVal)
    },
    searchNetwork(newVal) {
      this.$emit('update:network', newVal)
    },
    searchCountry(newVal) {
      this.$emit('update:country', newVal)
    },
    searchTags(newVal) {
      this.$emit('update:tags', newVal)
    },
  },
  computed: {
    networkOptions() {
      return this.networks ? this.networks.map(n => {
        return {
          id: n.id,
          name: n.name
        }
      }) : []
    },
    countryOptions() {
      // Return unique countries
      let ret = []

      if (this.groups) {
        this.groups.forEach(g => {
          if (g.country && !ret.find(g2 => {
            return g2.country && g2.country.localeCompare(g.country) === 0
          })) {
            ret.push({
              country: g.country
            })
          }
        })
      }

      return ret.sort((a, b) => {
        return a.country.localeCompare(b.country)
      })
    },
    translatedNetworks() {
      return this.$lang.get('networks.network')
    },
    translatedCountries() {
      return this.$lang.get('groups.search_country_placeholder')
    },
    translatedSearchNamePlaceholder() {
      return this.$lang.get('groups.search_name_placeholder')
    },
    translatedSearchLocationPlaceholder() {
      return this.$lang.get('groups.search_location_placeholder')
    },
    translatedSearchTagsPlaceholder() {
      return this.$lang.get('groups.search_tags_placeholder')
    }
  },
  created() {
    // Multiselect's v-model uses the options object, so find the relevant one.
    this.searchNetwork = this.networkOptions.find(n => n.id === this.network)
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto auto auto auto auto auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr;
  }
}
</style>