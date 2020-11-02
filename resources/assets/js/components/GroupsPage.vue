<template>
  <div>
<!--    TODO Notification box - when does this happen? -->
    <h1 class="d-flex justify-content-between">
      <div>
        {{ translatedTitle }}
        <b-img class="ml-2" src="/images/group_doodle_ico.svg" />
      </div>
      <div>
        <b-btn variant="primary" href="/group/create" v-if="canCreate">
          {{ translatedAddNewGroup}}
        </b-btn>
      </div>
    </h1>
    <b-tabs class="ourtabs w-100 mt-4" justified>
      <b-tab active class="pt-2">
        <template slot="title">
          <b class="text-uppercase">{{ translatedYourGroups }}</b>
        </template>
        <div class="pt-2 pb-2">
          <GroupsPageInfo />
          <div v-if="myGroups">
            <GroupsTable :groups="myGroups" class="mt-3" />
          </div>
        </div>
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase">{{ translatedNearestGroups }}</b>
        </template>
        <div v-if="!yourArea" class="text-center">
          {{ translatedYourArea1 }} <a :href="'/profile/edit/' + userId">{{ translatedYourArea2 }}</a>.
        </div>
        <div v-if="nearbyGroups">
          <GroupsTable :groups="nearbyGroups" class="mt-3" />
        </div>
      </b-tab>
      <b-tab class="pt-2">
        <template slot="title">
          <b class="text-uppercase">{{ translatedAllGroups }}</b>
        </template>
        TODO
      </b-tab>
    </b-tabs>
  </div>
</template>
<script>

import GroupsPageInfo from './GroupsPageInfo'
import GroupsTable from './GroupsTable'
export default {
  components: {GroupsTable, GroupsPageInfo},
  props: {
    yourGroups: {
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
    nearbyGroups: {
      type: Array,
      required: false,
      default: null
    },
    canCreate: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    allGroups() {
      let groups = this.$store.getters['groups/list']

      return groups ? groups : []
    },
    myGroups() {
      return this.allGroups.filter(g => {
        return g.ingroup
      })
    },
    nearGroups() {
      return this.allGroups.filter(g => {
        return g.nearby
      })
    },
    translatedTitle() {
      return this.$lang.get('groups.groups')
    },
    translatedAddNewGroup() {
      return this.$lang.get('groups.create_groups')
    },
    translatedYourGroups() {
      return this.$lang.get('groups.groups_title1')
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
    translatedAllGroups() {
      return this.$lang.get('groups.all_groups')
    }
  },
  mounted() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    // TODO Initial tab
    let groups = []

    if (this.yourGroups) {
      this.yourGroups.forEach(g => {
        g.ingroup = true

        groups.push(g)
      })
    }

    if (this.nearbyGroups) {
      this.yourGroups.forEach(g => {
        g.nearby = true

        groups.push(g)
      })
    }


    this.$store.dispatch('groups/setList', {
      groups
    })
  }
}
</script>