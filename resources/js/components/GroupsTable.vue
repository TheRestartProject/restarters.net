<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <div class="pl-4 pr-4 pt-2 pb-2 d-none d-md-block">
      <GroupsTableFilters
          v-if="search"
          :name.sync="searchName"
          :location.sync="searchLocation"
          :network.sync="searchNetwork"
          :country.sync="searchCountry"
          :tags.sync="searchTags"
          :networks="networks"
          :groups="groups"
          :all-group-tags="allGroupTags"
          :show-tags="showTags"
      />
    </div>
    <div class="d-block d-md-none" v-if="search">
      <div class="clickme d-flex justify-content-end pr-3 text-uppercase" v-if="!searchShow" @click="toggleFilters">
        <a href="#">{{ __('groups.show_filters') }}</a>&nbsp;<b-img class="plusminusicon" src="/images/add-icon.svg" />
      </div>
      <div class="clickme d-flex justify-content-end pr-3 text-uppercase" v-if="searchShow" @click="toggleFilters">
        <b-img class="plusminusicon" src="/images/minus-icon.svg" /><a href="#">&nbsp;{{ __('groups.hide_filters') }}</a>
      </div>
      <GroupsTableFilters
          v-if="searchShow"
          class="pl-1 pr-1 pt-2 pb-2"
          :name.sync="searchName"
          :location.sync="searchLocation"
          :network.sync="searchNetwork"
          :country.sync="searchCountry"
          :networks="networks"
          :groups="groups"
          :all-group-tags="allGroupTags"
          :show-tags="showTags"
      />
    </div>
    <hr class="d-block d-md-none" />
    <b-table :fields="fields" :items="itemsToShow" sort-null-last thead-tr-class="d-none d-md-table-row" :sort-compare="sortCompare">
      <template slot="head(group_image)">
        <span />
      </template>
      <template slot="cell(group_image)" slot-scope="data">
        <b-img-lazy :src="data.item.group_name.image" class="profile" @error.native="brokenProfileImage" v-if="data.item.group_name.image" />
        <b-img-lazy :src="defaultProfile" class="profile" v-else />
      </template>
      <template slot="head(group_name)">
        <b-img src="/icons/group_name_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="cell(group_name)" slot-scope="data">
        <a :href="'/group/view/' + data.item.group_name.idgroups">{{ data.item.group_name.name }}</a>
      </template>
      <template slot="head(location)">
        <b-img src="/icons/map_marker_ico.svg" class="mt-3 icon " />
      </template>
      <template slot="cell(location)" slot-scope="data">
        <div class="d-none d-md-block">
          {{ data.item.location.location.location }} <span class="text-muted small" v-if="data.item.location.location.distance">{{ distance(data.item.location.location.distance )}}&nbsp;km</span>
          <br />
          <span class="small text-muted">{{ data.item.location.location.country }}</span>
        </div>
      </template>
      <template slot="head(all_confirmed_hosts_count)">
        <b-img src="/icons/user_ico.svg" class="mt-3 iconsmall" />
      </template>
      <template slot="head(all_confirmed_restarters_count)">
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
            {{ __('groups.upcoming_none_planned') }}
          </div>
        </div>
      </template>
      <template slot="head(following)">
        <span />
      </template>
      <template slot="cell(following)" slot-scope="data">
        <div v-if="approve" class="cell-warning d-flex justify-content-around p-2">
          <a :href="'/group/edit/' + data.item.idgroups">{{ __('groups.group_requires_moderation') }}</a>
        </div>
        <b-btn variant="primary" class="text-nowrap mr-2" v-else-if="!data.item.following" :to="'/group/join/' + data.item.idgroups">
          <span class="d-block d-md-none">
            {{ __('groups.join_group_button_mobile') }}
          </span>
          <span class="d-none d-md-block">
            {{ __('groups.join_group_button') }}
          </span>
        </b-btn>
        <b-btn variant="primary" class="text-nowrap mr-2" v-else @click="leaveGroup(data.item.idgroups)">
          <span class="d-block d-md-none">
            {{ __('groups.leave_group_button_mobile') }}
          </span>
          <span class="d-none d-md-block">
            {{ __('groups.leave_group_button') }}
          </span>
        </b-btn>
        <ConfirmModal :key="'leavegroupmodal-' + data.item.idgroups" :ref="'confirmLeave-' + data.item.idgroups" @confirm="leaveConfirmed(data.item.idgroups)" :message="__('groups.leave_group_confirm')" />
      </template>
    </b-table>
  </div>
</template>
<script>
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'
import GroupsTableFilters from './GroupsTableFilters'
import ConfirmModal from './ConfirmModal'

export default {
  components: {ConfirmModal, GroupsTableFilters},
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
    },
    tab: {
      type: Number,
      required: false,
      default: 0
    },
    yourArea: {
      type: String,
      required: false,
      default: null
    },
    approve: {
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
        { key: 'location', label: 'Location', tdClass: "hidecell", thClass: "hidecell" },
        { key: 'all_confirmed_hosts_count', label: 'Hosts', sortable: true, tdClass: "hidecell text-center", thClass: "hidecell text-center pl-3" },
        { key: 'all_confirmed_restarters_count', label: 'Restarters', sortable: true, tdClass: "hidecell text-center", thClass: "hidecell text-center pl-3" },
        { key: 'next_event', label: 'Next Event', sortable: true, tdClass: "hidecell event", thClass: "hidecell" },
        { key: 'following' , label: 'Follow' }
      ],
      searchName: null,
      searchLocation: null,
      searchNetwork: null,
      searchCountry: null,
      searchShow: false,
      searchTags: null,
      show: 3,
      left: []
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

        if (this.searchNetwork) {
          match &= typeof g.networks.find(n => {
            return parseInt(this.searchNetwork) === parseInt(n)
          }) !== 'undefined'
        }

        if (this.searchName) {
          match &= g.name.toLowerCase().indexOf(this.searchName.toLowerCase()) !== -1
        }

        if (this.searchLocation) {
          if (g.location && g.location.location) {
            match &= g.location.location.toLowerCase().indexOf(this.searchLocation.toLowerCase()) !== -1
          }
        }

        if (this.searchCountry) {
          match &= g.location && g.location.country && g.location.country.toLowerCase().indexOf(this.searchCountry.country.toLowerCase()) !== -1
        }

        if (this.searchTags) {
          // Tag in common?
          if (this.searchTags.length) {
            const tagsInCommon = this.searchTags.filter(t => {
              return g.group_tags.indexOf(t.id) !== -1
            })

            match &= tagsInCommon.length > 0
          }
        }

        if (this.left.includes(g.idgroups)) {
          match = false
        }

        return match
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
          all_confirmed_hosts_count: g.all_confirmed_hosts_count,
          all_confirmed_restarters_count: g.all_confirmed_restarters_count,
          following: g.following
        }
      })
    },
    itemsToShow() {
      return this.items.slice(0, this.show)
    },
    translatedGroupCount() {
      return this.$lang.choice('groups.group_count', this.filteredGroups.length, {
        count: this.filteredGroups.length
      })
    },
},
  created() {
    // We might arrive on the page to filter by network.
    this.searchNetwork = this.network
  },
  mounted() {
    this.loadMore()
  },
  methods: {
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
    toggleFilters() {
      this.searchShow = !this.searchShow

      // Reset the search filters so that we don't end up filtered if we switch screen sizes.  It might be nice
      // to preserve the filter values, but that would be a bit of a faff with some two-way props bindings.
      this.searchName = null
      this.searchLocation = null
      this.searchNetwork = this.network
      this.searchCountry = null
    },
    sortCompare(aRow, bRow, key, sortDesc, formatter, compareOptions, compareLocale) {
      const a = aRow[key]
      const b = bRow[key]

      if (key === 'group_name') {
        // We need a custom sort because we are putting a link into the group field.
        return b.name.localeCompare(a.name, compareLocale, compareOptions)
      } else if (key === 'next_event') {
        // Sort no events to the end.
        if (!aRow.next_event && !bRow.next_event) {
          return 0
        } else if (aRow.next_event && !bRow.next_event) {
          return -1
        } else if (bRow.next_event && !aRow.next_event) {
          return 1
        } else {
          return new moment(aRow.group_name.next_event).unix() - new moment(bRow.group_name.next_event).unix()
        }
      } else if (key === 'all_hosts_count' || key === 'all_restarters_count') {
        if (parseInt(a) < parseInt(b)) {
          return -1
        } else if (parseInt(a) > parseInt(b)) {
          return 1
        } else {
          return 0
        }
      } else {
        return toString(a).localeCompare(toString(b), compareLocale, compareOptions)
      }
    },
    loadMore() {
      // We can't use a genuine infinite scroll because we need the data loaded into the table for filtering.  But
      // we can load it gradually so that the page looks more responsive.
      if (this.show < this.items.length) {
        this.show += 10
        setTimeout(this.loadMore, 1)
      }
    },
    leaveGroup(idgroups) {
      this.$refs['confirmLeave-' + idgroups].show()
    },
    async leaveConfirmed(idgroups) {
      await this.$store.dispatch('groups/unfollow', {
        idgroups: idgroups
      })

      this.left.push(idgroups)
    },
    distance(dist ) {
      if (dist < 5) {
        return Math.round(dist * 10) / 10
      } else {
        return Math.round(dist)
      }
    }
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
  height: 25px;
  margin-bottom: 5px;
}

.plusminusicon {
  width: 20px;
}

::v-deep .image {
  width: 90px;
}

::v-deep .event {
  width: 8rem;
}

::v-deep .table.b-table > thead > tr {
  background-position-x: center !important;
}

// The multiselect is used in a few places, and we have some inconsistencies in styling.  Here we force it to match
// the behaviour of the inputs.
::v-deep .multiselect {
  &.multiselect--active {
    border: 0 !important;

    input {
      margin-left: 6px;
      margin-top: 2px;
      margin-bottom: 4px;
    }
  }

  .multiselect__tags {
    padding: 2px 40px 3px 12px !important;
    border: 2px solid #222 !important;
  }
}

::v-deep .hidecell {
  display: none;

  @include media-breakpoint-up(md) {
    display: table-cell;
  }
}
</style>