<template>
  <CollapsibleSection border-shadow class="p-3" :show-horizontal-rule="false" heading-class="">
    <template slot="title">
      <div class="d-flex">
        <div class="align-self-center">
          {{ translatedYourGroupsHeading }}
        </div>
        <b-img class="height ml-4" src="/images/group_doodle_ico.svg" />
      </div>
    </template>

    <template slot="content">
      <div class="content">
        <div class="d-flex">
          <div class="w-50 pr-2 mt-3">
            <h3>
              {{ translatedGroupsHeading }}
            </h3>
            <p>
              {{ translatedCatchUp }}
            </p>
          </div>
          <div class="w-50 pl-2 mt-3">
            <div class=" d-flex justify-content-between">
              <div>
                <h3>
                  {{ translatedUpcomingEventsTitle }}
                </h3>
                <p v-if="events.length">
                  {{ translatedUpcomingEventsSubTitle }}
                </p>
                <p v-else>
                  {{ translatedNoUpcomingEvents }}.
                </p>
              </div>
              <div>
                <b-btn variant="primary" href="/party/create" class="text-nowrap">
                  {{ translatedAddEvent }}
                </b-btn>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex">
          <div class="w-50 pr-2 mt-3">
            <p class="border border-dark border-top-0 border-left-0 border-right-0" />
            <DashboardGroup
                v-for="g in myGroups"
                :group="g"
                :key="g.idgroups"
            />
          </div>
          <div class="w-50 pl-2 mt-3">
            <p class="border border-dark border-top-0 border-left-0 border-right-0" />
            <DashboardEvent v-for="e in events" :key="'event-' + e.idevents" :idevents="e.idevents" class="ml-1" />
          </div>
        </div>
        <div class="d-flex">
          <div class="w-50 pr-2">
            <div class="d-flex justify-content-end">
              <a href="/groups" class="mr-1">
                {{ translatedSeeAll }}
              </a>
            </div>
          </div>
          <div class="w-50">
            <div class="d-flex justify-content-end">
              <a href="/party" class="mr-1">
                {{ translatedSeeAll }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import DashboardGroup from './DashboardGroup'
import CollapsibleSection from './CollapsibleSection'
import DashboardEvent from './DashboardEvent'

export default {
  components: {DashboardEvent, CollapsibleSection, DashboardGroup},
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
    events() {
      return this.$store.getters['events/getByGroup'](null)
    },
    translatedSeeAll() {
      return this.$lang.get('dashboard.see_all_groups')
    },
    translatedCatchUp() {
      return this.$lang.get('dashboard.catch_up')
    },
    translatedYourGroupsHeading() {
      return this.$lang.get('dashboard.your_groups_heading')
    },
    translatedGroupsHeading() {
      return this.$lang.get('dashboard.groups_heading')
    },
    translatedUpcomingEventsTitle() {
      return this.$lang.get('dashboard.upcoming_events_title')
    },
    translatedUpcomingEventsSubTitle() {
      return this.$lang.get('dashboard.upcoming_events_subtitle')
    },
    translatedAddEvent() {
      return this.$lang.get('dashboard.add_event')
    },
    translatedNoUpcomingEvents() {
      return this.$lang.get('events.no_upcoming_for_your_groups')
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.content {
  border-top: 3px dashed black;
}

h3 {
  font-size: $font-size-base;
  font-weight: bold;
}

a {
  color: unset;
  text-decoration: underline;
}
</style>