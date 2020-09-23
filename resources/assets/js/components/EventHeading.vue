<template>
  <div>
    <div class="d-flex justify-content-between mb-3">
      <h1 class="d-block d-md-none">{{ translatedEvents }}</h1>
      <EventActions v-bind="$props" class="d-block d-md-none" />
    </div>
    <div class="border-top-very-thick border-bottom-thin mb-3">
      <div class="d-flex flex-wrap mt-4 mb-3 mb-md-3">
        <div class="bord d-flex w-xs-100 w-md-50">
          <div class="datebox">
            <span class="day">{{ date }}</span> <br />
            {{ month }}
          </div>
          <h1 class="ml-3 mr-3 d-none d-md-block">
            {{ event.venue ? event.venue : event.location }}
          </h1>
          <h2 class="ml-3 d-block d-md-none">
            {{ event.venue ? event.venue : event.location }}
          </h2>
        </div>
        <div class="pl-md-4 d-flex w-xs-100 w-md-50 maybeborder pt-3 p-md-0">
          <div class="d-flex justify-content-between w-100 flex-wrap">
            <div class="d-flex mr-2" v-if="event.the_group">
              <b-img @error="brokenGroupImage" :src="groupImage" class="groupImage d-none d-md-block" />
              <div v-html="translatedOrganised" class="ml-md-2"/>
            </div>
            <EventActions v-bind="$props" class="d-none d-md-block" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { DEFAULT_PROFILE } from '../constants'
import event from '../mixins/event'
import moment from 'moment'
import EventActions from './EventActions'

// TODO Discuss criteria for event delete with Neil.

export default {
  components: {EventActions},
  mixins: [event],
  computed: {
    canInvite() {
      // TODO Check this logic with Neil
      return this.upcoming && this.attending && this.attending.role === HOST;
    },
    start() {
      return this.event.start.substring(0, 5)
    },
    end() {
      return this.event.end.substring(0, 5)
    },
    date() {
      return new moment(this.event.event_date).format('D')
    },
    month() {
      return new moment(this.event.event_date).format('MMM').toUpperCase()
    },
    groupImage() {
      return this.event.the_group && this.event.the_group.group_image ? ('/uploads/mid_' + this.event.the_group.group_image.image.path) : DEFAULT_PROFILE
    },
    translatedEvents() {
      return this.$lang.get('events.events')
    },
    translatedOrganised() {
      // TODO not good to construct HTML here, but we will fix this when we change past events to use this component.
      console.log("Event", this.event)
      return this.$lang.get('events.organised_by', {
        group: '<br class="d-none d-md-block"/><b><a href="/group/view/' + this.event.the_group.idgroups  + '">' + this.event.the_group.name.trim() + '</a></b>'
      })
    },
  },
  methods: {
    brokenGroupImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}

.border-bottom-thin {
  border-bottom: 1px solid $black;
}

.bord {
  @include media-breakpoint-up(md) {
    border-right: 1px solid $black;
  }
}

.groupImage {
  width: 50px;
  height: 50px;
  object-fit: cover;
}

.datebox {
  color: white;
  background-color: $black;
  text-align: center;
  min-width: 70px;
  min-height: 70px;
  max-width: 70px;
  max-height: 70px;
  padding-top: 8px;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }
}

.maybeborder {
  @include media-breakpoint-down(sm) {
    border-top: 1px solid $black;
  }
}
</style>