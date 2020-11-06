<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <div v-if="search" class="p-4 layout">
      <b-form-input
          v-model="filter"
          type="search"
          :placeholder="translatedSearchPlaceholder"
          class="ml-md-3 mr-md-2"
      />
      <div />
      <multiselect
          v-model="network"
          :placeholder="translatedNetworks"
          :options="networkOptions"
          track-by="id"
          label="name"
          :multiple="false"
          :allow-empty="false"
          deselect-label=""
          :taggable="false"
          selectLabel=""
          class="ml-md-2 mr-md-3 mt-0 mb-0"
      />
    </div>
    <b-table :fields="fields" :items="items" sort-null-last :filter="filter">
      <template slot="head(group_image)">
        <span />
      </template>
      <template slot="cell(group_image)" slot-scope="data">
        <b-img-lazy :src="data.item.image" class="profile" @error.native="brokenProfileImage" v-if="data.item.image" />
        <b-img-lazy :src="defaultProfile" class="profile" v-else />
      </template>
      <template slot="head(group_name)">
        <b-img src="/icons/group_name_ico.svg" class="mt-3 iconsmall" />
      </template>
      <template slot="cell(group_name)" slot-scope="data">
        <a :href="'/group/view/' + data.item.group_name.idgroups">{{ data.item.group_name.name }}</a>
      </template>
      <template slot="head(location)">
        <b-img src="/icons/map_marker_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="head(all_hosts_count)">
        <b-img src="/icons/user_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="head(all_restarters_count)">
        <b-img src="/icons/volunteer_ico.svg" class="mt-3 icon" />
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
    </b-table>
  </div>
</template>
<script>
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'

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
      ],
      filter: null,
      network: null
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    filteredGroups() {
      return this.groups.filter(g => {
        // Groups can be in multiple networks.
        if (!this.network) {
          return true
        }

        return g.networks.find(n => {
          return parseInt(this.network.id) === parseInt(n)
        })
      })
    },
    networkOptions() {
      return this.networks.map(n => {
        return {
          id: n.id,
          name: n.name
        }
      })
    },
    items() {
      return this.filteredGroups.map(g => {
        return {
          group_image: g.group_image ? g.group_image : DEFAULT_PROFILE,
          group_name: g,
          location: g.location,
          next_event: g.next_event ? (new moment(g.next_event).format(DATE_FORMAT)) : null,
          all_hosts_count: g.all_hosts_count,
          all_restarters_count: g.all_restarters_count
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
    translatedNetworks() {
      return this.$lang.get('networks.network')
    },
    translatedSearchPlaceholder() {
      return this.$lang.get('groups.search_placeholder')
    }
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
  width: 12rem;
}

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: 1fr;
  }
}
</style>