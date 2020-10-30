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
            <span class="day align-top">{{ dayofmonth }}</span> <br />
            <span>
              {{ month }}
            </span>
          </div>
          <div class=" d-none d-md-block">
            <h1 class="ml-3 mr-3 mb-0 centreme">
              {{ event.venue ? event.venue : event.location }}
            </h1>
          </div>
          <h2 class="ml-3 d-block d-md-none">
            {{ event.venue ? event.venue : event.location }}
          </h2>
        </div>
        <div class="pl-md-4 d-flex w-xs-100 w-md-50 maybeborder pt-3 p-md-0 d-flex flex-column justify-content-center">
          <div class="d-flex justify-content-between w-100">
            <div class="d-flex mr-2" v-if="event.the_group">
              <b-img @error="brokenGroupImage" :src="groupImage" class="groupImage d-none d-md-block" />
              <div class="d-flex flex-wrap ml-md-2">
                {{ translatedOrganised }}&nbsp;
                <br class="d-none d-md-block"/>
                <b>
                  <a :href="'/group/view/' + event.the_group.idgroups">
                    {{ event.the_group.name.trim() }}
                  </a>
                </b>
              </div>
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

export default {
  components: {EventActions},
  mixins: [event],
  computed: {
    groupImage() {
      return this.event.the_group && this.event.the_group.group_image ? ('/uploads/mid_' + this.event.the_group.group_image.image.path) : DEFAULT_PROFILE
    },
    translatedEvents() {
      return this.$lang.get('events.events')
    },
    translatedOrganised() {
      // Existing translations may have a :group parameter, so set that empty so that it doesn't appear in the result.
      // We no longer use that parameter because the design has different styling for the translated text and the
      // group name.
      return this.$lang.get('events.organised_by', {
        group: ''
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

.centreme {
  align-items: center;
  display: flex !important;
}
</style>