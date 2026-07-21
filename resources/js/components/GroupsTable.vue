<template>
  <div>
    <p v-if="count" v-html="translatedGroupCount" />
    <div class="pl-4 pr-4 pt-2 pb-2 d-none d-md-block">
      <GroupsTableFilters
          v-if="search"
          :name.sync="searchName"
          :tags.sync="searchTags"
          :all-group-tags="allGroupTags"
          :show-tags="showTags"
      />
    </div>
    <div class="d-block d-md-none" v-if="search">
      <div class="clickme d-flex justify-content-end pr-3 text-uppercase" v-if="!searchShow" @click="toggleFilters">
        <a href="#">{{ __('groups.show_filters') }}</a>&nbsp;<b-img class="plusminusicon" :src="imageUrl('/images/add-icon.svg')" />
      </div>
      <div class="clickme d-flex justify-content-end pr-3 text-uppercase" v-if="searchShow" @click="toggleFilters">
        <b-img class="plusminusicon" :src="imageUrl('/images/minus-icon.svg')" /><a href="#">&nbsp;{{ __('groups.hide_filters') }}</a>
      </div>
      <GroupsTableFilters
          v-if="searchShow"
          class="pl-1 pr-1 pt-2 pb-2"
          :name.sync="searchName"
          :tags.sync="searchTags"
          :all-group-tags="allGroupTags"
          :show-tags="showTags"
      />
    </div>
    <hr class="d-block d-md-none" />
    <b-table :fields="fields" :items="itemsToShow" sort-null-last thead-tr-class="d-none d-md-table-row" :sort-compare="sortCompare"
             :tbody-tr-class="rowClass"
             @row-hovered="rowHovered" @row-unhovered="rowUnhovered"
    >
      <template slot="head(group_image)">
        <span />
      </template>
      <template slot="cell(group_image)" slot-scope="data">
        <b-img-lazy :src="groupImage(data.item)" class="profile" @error.native="brokenProfileImage" v-if="groupImage(data.item)" />
        <b-img-lazy :src="defaultProfile" class="profile" v-else />
      </template>
      <template slot="head(group_name)">
        <b-img :src="imageUrl('/icons/group_name_ico.svg')" class="mt-3 icon" />
      </template>
      <template slot="cell(group_name)" slot-scope="data">
        <!-- The template compiler condenses the newline between these two, so
             without the margin the badge sits hard against the end of the name. -->
        <a :href="'/group/view/' + (data.item.idgroups || data.item.id)" class="mr-2">{{ data.item.name }}</a>
        <GroupArchivedBadge :idgroups="data.item.idgroups || data.item.id" />
        <div v-if="showTags && data.item.group_tags_full && data.item.group_tags_full.length" class="mt-1">
          <b-badge
              v-for="tag in visibleTags(data.item.group_tags_full)"
              :key="tag.id"
              variant="secondary"
              class="mr-1 tag-badge"
          >{{ tag.name }}</b-badge>
        </div>
      </template>
      <template slot="head(location)">
        <b-img :src="imageUrl('/icons/map_marker_ico.svg')" class="mt-3 icon " />
      </template>
      <template slot="cell(location)" slot-scope="data">
        <div class="d-none d-md-block" v-if="data.item.location && data.item.location.location">
          {{ data.item.location.location }} <span class="text-muted small" v-if="data.item.location.distance">{{ distance(data.item.location.distance )}}&nbsp;km</span>
          <br />
          <span class="small text-muted">{{ data.item.location.country }}</span>
        </div>
      </template>
      <template slot="head(next_event)">
        <b-img :src="imageUrl('/icons/events_ico.svg')" class="mt-3 icon" />
      </template>
      <template slot="cell(next_event)" slot-scope="data">
        <div>
          <div v-if="data.item.next_event">
            {{ formatDate(data.item.next_event) }}
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
        <div class="cell-warning d-flex justify-content-around p-2">
          <a :href="'/group/edit/' + data.item.id">{{ __('groups.group_requires_moderation') }}</a>
        </div>
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
import images from '../mixins/images'
import moment from 'moment'
import GroupsTableFilters from './GroupsTableFilters.vue'
import GroupArchivedBadge from "./GroupArchivedBadge.vue";
import { matchesFilters } from '../misc/groupFilter'

// Rows shown per page. Small enough not to render a thousand rows at once,
// big enough that zooming out visibly fills the list.
const PAGE_SIZE = 25
import InfiniteLoading from 'vue-infinite-loading'


export default {
  components: {GroupArchivedBadge, GroupsTableFilters, InfiniteLoading},
  mixins: [images],
  props: {
    groupids: {
      type: Array,
      required: true
    },
    // Where the map is centred, so the list can be ordered by what's nearest to
    // the middle of what the user is looking at.  Null away from the map.
    centre: {
      type: Object,
      required: false,
      default: null
    },
    // Group id whose row should be highlighted (set when its map pin is hovered).
    hover: {
      type: Number,
      required: false,
      default: null
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
    approve: {
      type: Boolean,
      required: false,
      default: false
    },
    search: {
      type: Boolean,
      required: false,
      default: false
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
  },
  data () {
    return {
      searchName: null,
      searchTags: null,
      searchShow: false,
      show: PAGE_SIZE
    }
  },
  computed: {
    pageSize() {
      return PAGE_SIZE
    },
    fields() {
      const fields = [
        { key: 'group_image', label: 'Group Image', tdClass: 'image'},
        { key: 'group_name', label: 'Group Name', sortable: true },
        { key: 'location', label: 'Location', tdClass: "hidecell", thClass: "hidecell" },
        { key: 'next_event', label: 'Next Event', sortable: true, tdClass: "hidecell event", thClass: "hidecell" },
      ]

      if (this.approve) {
        // Moderation reuses this table, and its rows need a way through to the
        // group that is waiting to be approved.
        fields.push({ key: 'following', label: 'Moderate' })
      }

      return fields
    },
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    groups() {
      return this.$store.getters['groups/list']
    },
    items() {
      return this.groups.filter((g) => this.groupids.includes(g.id))
    },
    activeFilters() {
      return { name: this.searchName, tags: this.searchTags }
    },
    filteredItems() {
      // The same predicate the map uses, so the pins and the rows can't disagree.
      return this.items.filter(g => matchesFilters(g, this.activeFilters))
    },
    itemsToShow() {
      // Sort before slicing, so the first page is the first groups in order
      // rather than whichever ones happened to load first.
      const items = [...this.filteredItems].sort((a, b) => {
        if (this.centre) {
          // Nearest the middle of the map first: someone looking at a map wants
          // what's in front of them, and alphabetical order says nothing about
          // where a group is.  Groups we can't place go last.
          const da = this.distanceFromCentre(a)
          const db = this.distanceFromCentre(b)

          if (da !== db) {
            return da - db
          }
        }

        return a.name.localeCompare(b.name)
      })

      return items.slice(0, this.show)
    },
    translatedGroupCount() {
      return this.__('groups.group_count', {
        count: this.items.length
      })
    },
  },
  watch: {
    groupids(newVal, oldVal) {
      // The map is showing a different set of groups, so start the list again
      // from the top. Without this the row count stays at whatever the
      // infinite scroll had reached, however much the viewport changes.
      // Compared by contents, not identity: the parent hands over a freshly
      // built array whenever the store changes, and resetting on that would
      // throw away the user's scrolling.
      const same = newVal.length === oldVal.length && newVal.every((id, i) => id === oldVal[i])

      if (!same) {
        this.show = PAGE_SIZE
      }
    },
    activeFilters: {
      handler(newVal) {
        // Tell the map, so filtering moves the pins rather than just shortening
        // the list underneath them.
        this.$emit('update:filters', newVal)
      },
      deep: true
    },
    itemsToShow: {
      immediate: true,
      handler(newVal) {
        // Hydrate the visible rows in one batched call (image, location
        // text, counts, next event, tag names). The store skips ids that
        // are already hydrated or in flight.
        const ids = newVal.map(g => g.id)

        if (ids.length) {
          this.$store.dispatch('groups/hydrate', { ids })
        }
      }
    }
  },
  methods: {
    distanceFromCentre(group) {
      // Only ever compared with each other, so the flat-earth approximation is
      // fine and avoids the cost of a great-circle calculation per row.
      const lat = group.location && group.location.lat != null ? group.location.lat : group.lat
      const lng = group.location && group.location.lng != null ? group.location.lng : group.lng

      if (lat == null || lng == null || isNaN(+lat) || isNaN(+lng)) {
        return Number.MAX_VALUE
      }

      return Math.sqrt((+lat - this.centre.lat) ** 2 + (+lng - this.centre.lng) ** 2)
    },
    eventStart(event) {
      // next_event is an object ({start}) from the v2 APIs but a plain date
      // string in the moderation store (newToOld).
      return event && event.start ? event.start : event
    },
    formatDate(date) {
      return new moment(this.eventStart(date)).format('ddd Do MMM YYYY')
    },
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
    groupImage(item) {
      // The v2 APIs return a bare path; the moderation store and old
      // server-rendered data are already prefixed.
      if (!item.image) {
        return null
      }

      return item.image.startsWith('/') || item.image.startsWith('http') ? item.image : '/uploads/mid_' + item.image
    },
    sortCompare(aRow, bRow, key, sortDesc, formatter, compareOptions, compareLocale) {
      const a = aRow[key]
      const b = bRow[key]

      if (key === 'group_name') {
        // We need a custom sort because we are putting a link into the group field.
        return aRow.name.localeCompare(bRow.name, compareLocale, compareOptions)
      } else if (key === 'next_event') {
        // Sort no events to the end.
        if (!aRow.next_event && !bRow.next_event) {
          return 0
        } else if (aRow.next_event && !bRow.next_event) {
          return -1
        } else if (bRow.next_event && !aRow.next_event) {
          return 1
        } else {
          return new moment(this.eventStart(aRow.next_event)).unix() - new moment(this.eventStart(bRow.next_event)).unix()
        }
      } else {
        return String(a).localeCompare(String(b), compareLocale, compareOptions)
      }
    },
    loadMore($state) {
      if (this.show < this.items.length) {
        this.show += PAGE_SIZE
        $state.loaded()
      } else {
        $state.complete()
      }
    },
    distance(dist ) {
      if (dist < 5) {
        return Math.round(dist * 10) / 10
      } else {
        return Math.round(dist)
      }
    },
    toggleFilters() {
      this.searchShow = !this.searchShow
    },
    rowHovered(item, index, event) {
      this.$emit('update:hover', item.id)
    },
    rowUnhovered(item, index, event) {
      this.$emit('update:hover', null)
    },
    rowClass(item, type) {
      return item && type === 'row' && item.id === this.hover ? 'group-row-hover' : ''
    },
    visibleTags(tags) {
      // Filter tags to only show those the user has access to view
      // allGroupTags contains the tags the user can see (admin sees all, NC sees their networks)
      if (!this.allGroupTags || !tags) {
        return []
      }
      // Resolve names from allGroupTags: index entries only carry tag ids
      // until the row is hydrated.
      const byId = new Map(this.allGroupTags.map(t => [t.id, t]))
      return tags.filter(t => byId.has(t.id)).map(t => t.name ? t : byId.get(t.id))
    }
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

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

.tag-badge {
  font-size: 0.75rem;
  font-weight: normal;
  padding: 0.2em 0.5em;
}

// Row whose map pin is hovered. ::v-deep because the tr is rendered inside
// b-table, outside this component's scope attribute.
::v-deep .group-row-hover td {
  background-color: #fff3cd;
}
</style>