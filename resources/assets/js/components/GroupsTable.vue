<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <div v-if="search" class="pl-4 pr-4 pt-2 pb-2 layout">
      <b-form-input
          v-model="searchName"
          type="search"
          :placeholder="translatedSearchNamePlaceholder"
      />
      <div />
      <b-form-input
          v-model="searchLocation"
          type="search"
          :placeholder="translatedSearchLocationPlaceholder"
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
          class="mt-0 mb-0"
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
          class="mt-0 mb-0"
      />
    </div>
    <b-table :fields="fields" :items="items" sort-null-last>
      <template slot="head(group_image)">
        <span />
      </template>
      <template slot="cell(group_image)" slot-scope="data">
        <b-img-lazy :src="data.item.image" class="profile" @error.native="brokenProfileImage" v-if="data.item.image" />
        <b-img-lazy :src="defaultProfile" class="profile" v-else />
      </template>
      <template slot="head(group_name)">
        <b-img src="/icons/group_name_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="cell(group_name)" slot-scope="data">
        <a :href="'/group/view/' + data.item.group_name.idgroups">{{ data.item.group_name.name }}</a>
      </template>
      <template slot="head(location)">
        <b-img src="/icons/map_marker_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="cell(location)" slot-scope="data">
        {{ data.item.location.location }}
        <br />
        <span class="small text-muted">{{ data.item.location.country }}</span>
      </template>
      <template slot="head(all_hosts_count)">
        <b-img src="/icons/user_ico.svg" class="mt-3 iconsmall" />
      </template>
      <template slot="head(all_restarters_count)">
        <b-img src="/icons/volunteer_ico-thick.svg" class="mt-3 icon" />
      </template>
      <template slot="head(next_event)">
        <b-img src="/icons/events_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="cell(next_event)" slot-scope="data">
        <div>
          <div v-if="data.item.next_event">
            {{ data.item.next_event }}
          </div>
          <div v-else>
            {{ translatedNonePlanned}}
          </div>
        </div>
      </template>
      <template slot="head(follow)">
        <span />
      </template>
      <template slot="cell(follow)" slot-scope="data">
        <b-btn variant="primary" class="text-nowrap mr-2" v-if="data.item.follow" :to="'/group/join/' + data.item.idgroups">
          {{ translatedFollow }}
        </b-btn>
      </template>
    </b-table>
  </div>
</template>
<script>
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'

// TODO Input box height compared to select

export default {
  props: {
    groups: {
      type: Array,
      required: true
    },
    count: {
      type: Boolean,
      required: false,
      default: false
    },
    search: {
      type: Boolean,
      required: false,
      default: false
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
    }
  },
  data () {
    return {
      fields: [
        { key: 'group_image', label: 'Group Image', tdClass: 'image'},
        { key: 'group_name', label: 'Group Name', sortable: true },
        { key: 'location', label: 'Location' },
        { key: 'all_hosts_count', label: 'Hosts', sortable: true },
        { key: 'all_restarters_count', label: 'Restarters', sortable: true },
        { key: 'next_event', label: 'Next Event', sortable: true, tdClass: 'event' },
        { key: 'follow' , label: 'Follow' }
      ],
      searchName: null,
      searchLocation: null,
      searchNetwork: null,
      searchCountry: null
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    filteredGroups() {
      return this.groups.filter(g => {
        // Groups can be in multiple networks.
        let match = true

        if (this.network) {
          match &= g.networks.find(n => {
            return parseInt(this.searchNetwork.id) === parseInt(n)
          })
        }

        if (this.searchName) {
          match &= g.name.toLowerCase().indexOf(this.searchName.toLowerCase()) !== -1
        }

        if (this.searchLocation) {
          match &= g.location.toLowerCase().indexOf(this.searchLocation.toLowerCase()) !== -1
        }

        if (this.searchCountry) {
          console.log("Search country", g, this.searchCountry)
          match &= g.country && g.country.toLowerCase().indexOf(this.searchCountry.country.toLowerCase()) !== -1
        }

        return match
      })
    },
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
    items() {
      return this.filteredGroups.map(g => {
        return {
          idgroups: g.idgroups,
          group_image: g.group_image ? g.group_image : DEFAULT_PROFILE,
          group_name: g,
          location: g,
          next_event: g.next_event ? (new moment(g.next_event).format(DATE_FORMAT)) : null,
          all_hosts_count: g.all_hosts_count,
          all_restarters_count: g.all_restarters_count,
          follow: !g.ingroup
        }
      })
    },
    translatedNonePlanned() {
      return this.$lang.get('groups.upcoming_none_planned')
    },
    translatedGroupCount() {
      return this.$lang.choice('groups.group_count', this.filteredGroups.length, {
        count: this.filteredGroups.length
      })
    },
    translatedFollow() {
      return this.$lang.get('groups.join_group_button')
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
    }
  },
  created() {
    // Multiselect's v-model uses the options object, so find the relevant one.
    this.searchNetwork = this.networkOptions.find(n => n.id === this.network)
  },
  methods: {
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.profile {
  border: 1px solid black;
}

.icon {
  width: 30px;
  height: 30px;
}

.iconsmall {
  width: 27px;
}

/deep/ .image {
  width: 90px;
}

/deep/ .event {
  width: 8rem;
}

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto auto auto auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr;
  }
}

/deep/ .table.b-table > thead > tr {
  background-position-x: center !important;
}
</style>