<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <b-table :fields="fields" :items="items" sort-icon-left sort-null-last>
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
      <template slot="head(all_hosts_count)">
        <b-img src="/icons/volunteer_ico.svg" class="mt-3 icon" />
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
      ]
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    items() {
      return this.groups.map(g => {
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
      return this.$lang.choice('groups.group_count', this.groups.length, {
        count: this.groups.length
      })
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

.profile {
  border: 1px solid black;
}

.icon {
  width: 30px;
  height: 30px;
}

/deep/ .image {
  width: 90px;
}

/deep/ .event {
  width: 12rem;
}
</style>