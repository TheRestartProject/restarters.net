<template>
  <div class="gestf-layout pl-2 pr-2">
    <b-form-input
        v-model="searchTitle"
        type="search"
        :placeholder="__('events.search_title_placeholder')"
        class="mb-1 mb-md-0 search"
    />
    <multiselect
        v-model="searchCountry"
        :placeholder="__('events.search_country_placeholder')"
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
        @open="$emit('countryOpen')"
        @close="$emit('countryClose')"
        :limit="5"
        open-direction="bottom"
    />
    <b-form-datepicker class="datepicker" v-model="searchStart" :placeholder="__('events.search_start_placeholder')" @shown="$emit('calendarOpen')" @hidden="$emit('calendarClose')"></b-form-datepicker>
    <b-form-datepicker class="datepicker" v-model="searchEnd" :placeholder="__('events.search_end_placeholder')" @shown="$emit('calendarOpen')" @hidden="$emit('calendarClose')"></b-form-datepicker>
  </div>
</template>
<script>
export default {
  props: {
    events: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      required: false,
      default: null
    },
    start: {
      type: String,
      required: false,
      default: null
    },
    end: {
      type: String,
      required: false,
      default: null
    }
  },
  data () {
    return {
      searchTitle: null,
      searchCountry: null,
      searchStart: null,
      searchEnd: null
    }
  },
  watch: {
    searchTitle(newVal) {
      this.$emit('update:title', newVal)
    },
    searchCountry(newVal) {
      this.$emit('update:country', newVal)
    },
    searchStart(newVal) {
      this.$emit('update:start', newVal)
    },
    searchEnd(newVal) {
      this.$emit('update:end', newVal)
    },
  },
  computed: {
    countryOptions() {
      // Return unique countries
      let ret = []

      if (this.events) {
        this.events.forEach(e => {
          if (e.group.country && !ret.find(e2 => {
            return e2.country && e2.country.localeCompare(e.group.country) === 0
          })) {
            ret.push({
              country: e.group.country
            })
          }
        })
      }

      return ret.sort((a, b) => {
        return a.country.localeCompare(b.country)
      })
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.gestf-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto;

  @include media-breakpoint-up(md) {
    grid-column-gap: 20px;
    grid-template-columns: repeat( auto-fit, minmax(0px, 1fr) );
    grid-template-rows: 1fr;
  }
}

.b-form-datepicker.form-control {
  padding: 0 10px;
}

::v-deep .datepicker {
  & label {
    padding-bottom: 0;
    border: 0;
    margin: 0;
    font-weight: normal;
  }

  .btn {
    padding: 0.4rem 0.3rem !important;
  }

  .btn-primary {
    background-color: $brand-orange !important;
    color: $black !important;
  }
}

.search {
  height: 45px;
}
</style>
