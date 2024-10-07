<template>
  <div>
    <h1 class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="mt-2">
        {{ __('groups.groups') }}
        </div>
        <b-img class="height ml-4" src="/images/group_doodle_ico.svg" />
      </div>
      <div>
        <b-btn variant="primary" href="/group/create" v-if="canCreate">
          <span class="d-block d-lg-none">
            {{ __('groups.create_groups_mobile2') }}
          </span>
          <span class="d-none d-lg-block">
            {{ __('groups.create_groups') }}
          </span>
        </b-btn>
      </div>
    </h1>
    <b-tabs class="ourtabs w-100 mt-4" justified v-model="currentTab">
      <b-tab class="pt-2" lazy>
        <template slot="title">
          <b class="text-uppercase d-block d-md-none">{{ __('groups.groups_title1_mobile') }}</b>
          <b class="text-uppercase d-none d-md-block">{{ __('groups.groups_title1') }}</b>
        </template>
        <div class="pt-2 pb-2">
          <div v-if="yourGroups.length">
            <GroupsTable
                :groupids="yourGroups"
                class="mt-3"
                :tab="currentTab"
                @nearest="currentTab = 1"
                your-area="yourArea"
            />
          </div>
          <div v-else class="mt-2 mb-2 text-center" v-html="__('groups.no_groups_mine')" />
        </div>
      </b-tab>
      <b-tab class="pt-2" lazy>
        <template slot="title">
          <b class="text-uppercase d-block d-lg-none">{{ __('groups.groups_title2_mobile') }}</b>
          <b class="text-uppercase d-none d-lg-block">{{ __('groups.groups_title2') }}</b>
        </template>
        <div v-if="nearbyGroups.length">
          <GroupMapAndList :initial-bounds="nearbyGroups" />
        </div>
        <div v-else class="mt-2 mb-2 text-center">
          <div v-if="yourArea" v-html="__('groups.no_groups_nearest_with_location')" />
          <div v-else v-html="__('groups.no_groups_nearest_no_location')" />
        </div>
      </b-tab>
      <b-tab class="pt-2" lazy>
        <template slot="title">
          <b class="text-uppercase d-block d-md-none">{{ __('groups.all_groups_mobile') }}</b>
          <b class="text-uppercase d-none d-md-block">{{ __('groups.all_groups') }}</b>
        </template>
        <GroupMapAndList :initial-bounds="[ [ -62.26792262941758, -389.53125 ], [ 86.57422361983717, 389.53125 ] ]" />
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>
import GroupsTable from './GroupsTable'
import auth from '../mixins/auth'
import GroupMapAndList from "./GroupMapAndList.vue";

export default {
  components: {GroupMapAndList, GroupsTable},
  mixins: [ auth ],
  props: {
    network: {
      type: Number,
      required: false,
      default: null
    },
    tab: {
      type: String,
      required: false,
      default: 'mine'
    },
    yourGroups: {
      type: Array,
      required: true
    },
    nearbyGroups: {
      type: Array,
      required: true
    },
    yourArea: {
      type: String,
      required: false,
      default: null
    },
    yourLat: {
      type: String,
      required: false,
      default: null
    },
    yourLng: {
      type: String,
      required: false,
      default: null
    },
    userId: {
      type: Number,
      required: false,
      default: null
    },
    canCreate: {
      type: Boolean,
      required: false,
      default: false
    },
    showTags: {
      type: Boolean,
      required: false,
      default: false
    },
    networks: {
      type: Array,
      required: true
    },
    // TODO Check whether all these parameters are now used or can be removed
    allGroupTags: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      currentTab: 0
    }
  },
  computed: {
    groups() {
      let groups = this.$store.getters['groups/list']

      return groups ? groups.sort((a, b) => {
        return a.name.localeCompare(b.name)
      }) : []
    },
    nearestGroups() {
      return this.$lang.get('groups.nearest_groups', {
        location: this.yourArea
      })
    }
  },
  watch: {
    currentTab: {
      handler: function (newVal) {
        // We want to update the URL in the browser.  In a full app this would be done by the router, but hack it in
        // here.
        try {
          let tag = '';

          switch (newVal) {
            case 1:
              tag = 'nearby';
              break;
            case 2:
              tag = 'all';
              break;
            default:
              tag = 'mine';
              break;
          }

          if (!this.network) {
            // If we are vieiwng a specific network, don't mess with the URL as it's confusing.
            window.history.pushState(null, "Groups", "/group/" + tag);
          }

          // We want to make sure we have the groups in store.
          // - For the Your or Nearby tabs, we list all the groups in summary form (which is quick) and
          //   then in GroupsTable we will fetch any groups where we need to display the detail.
          // - For the App groups tab, we list all the groups with full details (which is slow).  This is
          //   because fetching each group individually via the API in GroupsTable would be much slower and
          //   hit API throttling.
          if (newVal == 2) {
            this.$store.dispatch('groups/list', {
              details: true
            })
          } else {
            this.$store.dispatch('groups/list')
          }
        } catch (e) {
          console.error("Failed to update URL")
        }
      },
      immediate: true
    }
  },
  created() {
    // We have three tabs, and might be asked to start on a specific one.
    switch (this.tab) {
      case 'nearby':
        this.currentTab = 1;
        break;
      case 'all':
      case 'network':
        this.currentTab = 2;
        break;
      default:
        this.currentTab = 0;
        break;
    }
  }
}
</script>
<style scoped lang="scss">
.height {
  height: 76px;
}
</style>