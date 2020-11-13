<template>
  <div>
<!--    TODO Notification box - when does this happen? -->
    <h1 class="d-flex justify-content-between">
      <div class="d-flex">
        {{ translatedTitle }}
        <b-img class="ml-2" src="/images/group_doodle_ico.svg" />
      </div>
      <div>
        <b-btn variant="primary" href="/group/create" v-if="canCreate">
          <span class="d-block d-md-none">
            {{ translatedAddNewGroupMobile }}
          </span>
          <span class="d-none d-md-block">
            {{ translatedAddNewGroup }}
          </span>
        </b-btn>
      </div>
    </h1>
    <b-tabs class="ourtabs w-100 mt-4" justified v-model="currentTab">
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase d-block d-md-none">{{ translatedYourGroupsMobile }}</b>
          <b class="text-uppercase d-none d-md-block">{{ translatedYourGroups }}</b>
        </template>
        <div class="pt-2 pb-2">
          <GroupsPageInfo @nearest="currentTab = 1"/>
          <div v-if="myGroups">
            <GroupsTable :groups="myGroups" class="mt-3" />
          </div>
        </div>
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase d-block d-md-none">{{ translatedNearestGroupsMobile }}</b>
          <b class="text-uppercase d-none d-md-block">{{ translatedNearestGroups }}</b>
        </template>
        <div v-if="!yourArea" class="text-center">
          {{ translatedYourArea1 }} <a :href="'/profile/edit/' + userId">{{ translatedYourArea2 }}</a>.
        </div>
        <div v-if="nearbyGroups">
          <GroupsTable :groups="nearbyGroups" class="mt-3" />
        </div>
        <div v-else>
          <p>
            {{ translatedNoGroupsNearYou }}
          </p>
          <p v-html="startAGroup" />
        </div>
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase d-block d-md-none">{{ translatedAllGroupsMobile }}</b>
          <b class="text-uppercase d-none d-md-block">{{ translatedAllGroups }}</b>
        </template>
        <GroupsTable :groups="groups" class="mt-3" count search :networks="networks" :network="network" :all-group-tags="allGroupTags" />
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>
import GroupsPageInfo from './GroupsPageInfo'
import GroupsTable from './GroupsTable'

// TODO Mobile layout
export default {
  components: {GroupsTable, GroupsPageInfo},
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
    yourGroups: {
      type: Array,
      required: false,
      default: null
    },
    nearbyGroups: {
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
    networks: {
      type: Array,
      required: true
    },
    startAGroup: {
      type: String,
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
    myGroups() {
      return this.groups.filter(g => {
        return g.ingroup
      })
    },
    nearGroups() {
      return this.groups.filter(g => {
        return g.nearby
      })
    },
    translatedTitle() {
      return this.$lang.get('groups.groups')
    },
    translatedAddNewGroup() {
      return this.$lang.get('groups.create_groups')
    },
    translatedAddNewGroupMobile() {
      return this.$lang.get('groups.create_groups_mobile2')
    },
    translatedYourGroups() {
      return this.$lang.get('groups.groups_title1')
    },
    translatedYourGroupsMobile() {
      return this.$lang.get('groups.groups_title1_mobile')
    },
    translatedNoGroupsNearYou() {
      return this.$lang.get('groups.no_groups_near_you', {
        area: this.yourArea ? (this.yourArea.charAt(0).toUpperCase() + this.yourArea.slice(1)) : ''
      })
    },
    translatedYourArea1() {
      return this.$lang.get('groups.your_area1')
    },
    translatedYourArea2() {
      return this.$lang.get('groups.your_area2')
    },
    translatedNearestGroups() {
      return this.$lang.get('groups.groups_title2')
    },
    translatedNearestGroupsMobile() {
      return this.$lang.get('groups.groups_title2_mobile')
    },
    translatedAllGroups() {
      return this.$lang.get('groups.all_groups')
    },
    translatedAllGroupsMobile() {
      return this.$lang.get('groups.all_groups_mobile')
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

        console.log("Route to", tag)
        window.history.pushState(null, "Groups", "/group/" + tag);
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
    let groups = {}

    this.allGroups.forEach(g => {
      groups[g.idgroups] = g
    })

    if (this.yourGroups) {
      this.yourGroups.forEach(g => {
        groups[g.idgroups].ingroup = true
      })
    }

    if (this.nearbyGroups) {
      this.yourGroups.forEach(g => {
        groups[g.idgroups].nearby = true
      })
    }

    this.$store.dispatch('groups/setList', {
      groups: Object.values(groups)
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