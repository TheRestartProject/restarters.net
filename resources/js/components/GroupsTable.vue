<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <b-table :fields="fields" :items="itemsToShow" sort-null-last thead-tr-class="d-none d-md-table-row" :sort-compare="sortCompare"
             @row-hovered="rowHovered" @row-unhovered="rowUnhovered"
    >
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
        <a :href="'/group/view/' + data.item.id">{{ data.item.name }}</a>
        <GroupArchivedBadge :idgroups="data.item.id" />
      </template>
      <template slot="head(location)">
        <b-img src="/icons/map_marker_ico.svg" class="mt-3 icon " />
      </template>
      <template slot="cell(location)" slot-scope="data">
        <div class="d-none d-md-block" v-if="data.item.location && data.item.location.location">
          {{ data.item.location.location }} <span class="text-muted small" v-if="data.item.location.distance">{{ distance(data.item.location.distance )}}&nbsp;km</span>
          <br />
          <span class="small text-muted">{{ data.item.location.country }}</span>
        </div>
      </template>
      <template slot="head(hosts)">
        <b-img src="/icons/user_ico.svg" class="mt-3 iconsmall" />
      </template>
      <template slot="head(restarters)">
        <b-img src="/icons/volunteer_ico-thick.svg" class="mt-3 icon" />
      </template>
      <template slot="head(next_event)">
        <b-img src="/icons/events_ico.svg" class="mt-3 icon" />
      </template>
      <template slot="cell(next_event)" slot-scope="data">
        <div>
          <div v-if="data.item.next_event">
            {{ formatDate(data.item.next_event.start) }}
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
          <a :href="'/group/edit/' + data.item.id">{{ __('groups.group_requires_moderation') }}</a>
        </div>
        <b-btn variant="primary" class="text-nowrap mr-2" v-else-if="!yourGroup(data.item.id)" :to="'/group/join/' + data.item.id">
          <span class="d-block d-md-none">
            {{ __('groups.join_group_button_mobile') }}
          </span>
          <span class="d-none d-md-block">
            {{ __('groups.join_group_button') }}
          </span>
        </b-btn>
        <b-btn variant="primary" class="text-nowrap mr-2" v-else @click="leaveGroup(data.item.id)">
          <span class="d-block d-md-none">
            {{ __('groups.leave_group_button_mobile') }}
          </span>
          <span class="d-none d-md-block">
            {{ __('groups.leave_group_button') }}
          </span>
        </b-btn>
        <ConfirmModal :key="'leavegroupmodal-' + data.item.id" :ref="'confirmLeave-' + data.item.id" @confirm="leaveConfirmed(data.item.id)" :message="__('groups.leave_group_confirm')" />
      </template>
    </b-table>
    <infinite-loading @infinite="loadMore">
      <span slot="no-results" />
      <span slot="no-more" />
    </infinite-loading>
  </div>
</template>
<script>
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'
import ConfirmModal from './ConfirmModal'
import GroupArchivedBadge from "./GroupArchivedBadge.vue";
import InfiniteLoading from 'vue-infinite-loading'


export default {
  components: {GroupArchivedBadge, ConfirmModal},
  props: {
    groupids: {
      type: Array,
      required: true
    },
    count: {
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
    yourGroups: {
      type: Array,
      required: false,
      default: () => [],
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
        { key: 'hosts', label: 'Hosts', sortable: true, tdClass: "hidecell text-center", thClass: "hidecell text-center pl-3" },
        { key: 'restarters', label: 'Restarters', sortable: true, tdClass: "hidecell text-center", thClass: "hidecell text-center pl-3" },
        { key: 'next_event', label: 'Next Event', sortable: true, tdClass: "hidecell event", thClass: "hidecell" },
        { key: 'following' , label: 'Follow' }
      ],
      show: 3,
      left: []
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    items() {
      const ret = this.$store.getters['groups/list'].filter((g) => {
        return this.groupids.includes(g.id)
      })
      return ret
    },
    itemsToShow() {
      const items = this.items.slice(0, this.show)

      items.sort((a, b) => {
        return a.name.localeCompare(b.name)
      })

      return items
    },
    translatedGroupCount() {
      return this.$lang.choice('groups.group_count', this.items.length, {
        count: this.items.length
      })
    },
  },
  watch: {
    async itemsToShow(newVal) {
      // We may need to fetch the group over the API if not in store.
      //
      // This is for the "your groups" or "other groups nearby" case.  For "all groups" it would result in too
      // many API calls, so we fetch those in a single slow API call.
      newVal.forEach(async (g) => {
        const group = this.$store.getters['groups/get'](g.id)

        if (!group || !group.location) {
          await this.$store.dispatch('groups/fetch', {
            id: g.id,
            includeStats: false
          })
        }
      })
    }
  },
  methods: {
    formatDate(date) {
      return new moment(date).format('ddd Do MMM YYYY')
    },
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
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
      } else if (key === 'hosts' || key === 'restarters') {
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
    loadMore($state) {
      if (this.show < this.items.length) {
        this.show++
        $state.loaded()
      } else {
        $state.complete()
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
    },
    yourGroup(id) {
      return this.yourGroups.includes(id)
    },
    rowHovered(item, index, event) {
      this.$emit('update:hover', item.id)
    },
    rowUnhovered(item, index, event) {
      this.$emit('update:hover', null)
    }
  },
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