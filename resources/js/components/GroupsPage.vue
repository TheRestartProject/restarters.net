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
                :groups="yourGroups"
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
          <p class="mt-1">
            {{ nearestGroups }}
            <a href="/profile/edit" class="small">{{ __('groups.nearest_groups_change') }}</a>.
          </p>
          <GroupsTable
              :groups="nearbyGroups"
              class="mt-3"
              :tab="currentTab"
              @all="currentTab = 2"
              your-area="yourArea"
          />
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
        <GroupsTable
            :groups="groups"
            class="mt-3"
            count
            search
            :networks="networks"
            :network="network"
            :all-group-tags="allGroupTags"
            :show-tags="showTags"
            :tab="currentTab"
            your-area="yourArea"
        />
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>
import GroupsTable from './GroupsTable.vue'
import auth from '../mixins/auth'

export default {
  components: {GroupsTable},
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
    allGroups: {
      type: Array,
      required: false,
      default: null
    },
    yourArea: {
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
    yourGroups() {
      return this.groups.filter(g => {
        return g.following
      })
    },
    nearbyGroups() {
      return this.groups.filter(g => {
        return g.nearby && !g.following
      }).sort((a, b) => {
        return a.distance - b.distance
      })
    },
    nearestGroups() {
      return this.$lang.get('groups.nearest_groups', {
        location: this.yourArea
      })
    }
  },
  watch: {
    currentTab(newVal) {
      // We want to update the URL in the browser.  In a full app this would be done by the router, but hack it in
      // here.
      try {
        let tag = '';

        switch (newVal) {
          case 1: tag = 'nearby'; break;
          case 2: tag = 'all'; break;
          default: tag = 'mine'; break;
        }

        if (!this.network) {
          // If we are vieiwng a specific network, don't mess with the URL as it's confusing.
          window.history.pushState(null, "Groups", "/group/" + tag);
        }
      } catch (e) {
        console.error("Failed to update URL")
      }
    }
  },
  created() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.$store.dispatch('groups/setList', {
      groups: Object.values(this.allGroups)
    })

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