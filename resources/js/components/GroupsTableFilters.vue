<template>
  <div class="gt-layout">
    <b-form-input
        v-model="searchName"
        type="search"
        :placeholder="__('groups.search_name_placeholder')"
        class="mb-1 mb-md-0"
    />
    <multiselect
        v-if="showTags"
        v-model="searchTags"
        :placeholder="__('groups.search_tags_placeholder')"
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
        :selectedLabel="__('partials.remove')"
        open-direction="bottom"
    />
    <b-form-input
        v-model="searchLocation"
        type="search"
        :placeholder="__('groups.search_location_placeholder')"
        class="mb-1 mb-md-0"
    />
    <multiselect
        v-model="searchCountry"
        :placeholder="__('groups.search_country_placeholder')"
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
        :selectedLabel="__('partials.remove')"
        open-direction="bottom"
    />
    <multiselect
        v-model="searchNetwork"
        :placeholder="__('networks.network')"
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
        :selectedLabel="__('partials.remove')"
        open-direction="bottom"
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
      type: Number,
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
    },
    showTags: {
      type: Boolean,
      required: false,
      default: false
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
      this.$emit('update:network', newVal ? newVal.id : null)
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
          if (g.location && g.location.country && !ret.find(g2 => {
            return g2.country && g2.country.localeCompare(g.location.country) === 0
          })) {
            ret.push({
              country: g.location.country
            })
          }
        })
      }

      return ret.sort((a, b) => {
        return a.country.localeCompare(b.country)
      })
    },
  },
  created() {
    // Multiselect's v-model uses the options object, so find the relevant one.
    this.searchNetwork = this.networkOptions.find(n => n.id === this.network)
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.gt-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto;

  @include media-breakpoint-up(md) {
    grid-column-gap: 20px;
    grid-template-columns: repeat( auto-fit, minmax(0px, 1fr) );
    grid-template-rows: 1fr;
  }
}
</style>