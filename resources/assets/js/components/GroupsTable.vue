<template>
  <div>
    <b-table :fields="fields" :items="items" sort-icon-left>
      <template slot="head(group_image)">
        <span />
      </template>
      <template slot="cell(group_image)" slot-scope="data">
        <b-img-lazy :src="data.item.group_image" class="profile" @error.native="brokenProfileImage" />
      </template>
      <template slot="head(group_name)">
        <span />
      </template>
      <template slot="cell(group_name)" slot-scope="data">
        <a :href="'/group/view/' + data.item.group_name.idgroups">{{ data.item.group_name.name }}</a>
      </template>
      <template slot="head(location)">
        <b-img src="/icons/map_marker_ico.svg" class="mt-3" />
      </template>
      <template slot="head(next_event)">
        <b-img src="/icons/events_ico.svg" class="mt-3" />
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
// TODO Leave group
import { DEFAULT_PROFILE } from '../constants'

export default {
  props: {
    groups: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      fields: [
        { key: 'group_image', label: 'Group Image', tdClass: 'image'},
        { key: 'group_name', label: 'Group Name' },
        { key: 'location', label: 'Location' },
        { key: 'next_event', label: 'Next Event', sortable: true, tdClass: 'event' },
      ]
    }
  },
  computed: {
    items() {
      return this.groups.map(g => {
        return {
          group_image: g.group_image ? g.group_image : DEFAULT_PROFILE,
          group_name: g,
          location: g.location,
          next_event: g.next_event
        }
      })
    },
    translatedNonePlanned() {
      return this.$lang.get('groups.upcoming_none_planned')
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

/deep/ .image {
  width: 90px;
}

/deep/ .event {
  width: 12rem;
}
</style>