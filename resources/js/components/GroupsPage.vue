<template>
  <div>
    <h1 class="d-flex justify-content-between">
      <div class="d-flex">
        <div class="mt-2">
        {{ __('groups.groups') }}
        </div>
        <b-img class="height ml-4" :src="imageUrl('/images/group_doodle_ico.svg')" />
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
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase d-block d-lg-none">{{ __('groups.groups_title2_mobile') }}</b>
          <b class="text-uppercase d-none d-lg-block">{{ __('groups.groups_title2') }}</b>
        </template>
        <div v-if="nearbyGroups.length">
          <GroupMapAndList :initial-bounds="nearbyGroups" :yourGroups="yourGroups"/>
        </div>
        <div v-else class="mt-2 mb-2 text-center">
          <div v-if="yourArea" v-html="__('groups.no_groups_nearest_with_location')" />
          <div v-else v-html="__('groups.no_groups_nearest_no_location')" />
        </div>
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>
import GroupsTable from './GroupsTable.vue'
import auth from '../mixins/auth'
import GroupMapAndList from "./GroupMapAndList.vue";
import images from '../mixins/images'

export default {
  components: {GroupMapAndList, GroupsTable},
  mixins: [ auth, images ],
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
    // Initialize directly from prop so lazy b-tab renders correctly on first mount.
    // Setting this in created() causes lazy tabs to miss the initial activation.
    const tabToIndex = ['all', 'network', 'nearby', 'other']
    return {
      currentTab: tabToIndex.includes(this.tab) ? 1 : 0
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
      return this.__('groups.nearest_groups', {
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
              tag = 'other';
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
        } catch (e) {
          console.error("Failed to update URL")
        }
      },
      immediate: true
    }
  },
  mounted() {
    if (this.currentTab === 0) {
      // Fetch our own groups.  We fetch them individually because there aren't that many.
      this.yourGroups.forEach((g) => {
        this.$store.dispatch('groups/fetch', {
          id: g,
          includeStats: false
        })
      })
    }
  }
}
</script>
<style scoped lang="scss">
.height {
  height: 76px;
}
</style>