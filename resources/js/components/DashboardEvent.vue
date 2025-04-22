<template>
  <div @click="goto" class="clickme">
    <div class="d-flex">
      <div class="datecell">
        <div class="datebox d-flex flex-column">
          <span class="day align-top">{{ dayofmonth }}</span>
          <span class="month">
            {{ month }}
          </span>
        </div>
      </div>
      <div class="ml-2 align-self-center flex-grow-1">
        <EventTitle :idevents="event.idevents" component="div" class="font-weight-bold" />
        <div class="small">
          {{ date }} {{ start }} <span class="d-none d-md-inline">- {{ end }}</span>
        </div>
      </div>
      <div class="ml-2 align-self-center">
        <b-img-lazy :src="event.group.image" class="profile mr-2" @error.native="brokenProfileImage" v-if="event.group.image" />
        <b-img-lazy :src="defaultProfile" class="profile mr-2" v-else />
      </div>
    </div>
    <hr />
  </div>
</template>
<script>
import event from '../mixins/event'
import moment from 'moment'
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import EventTitle from './EventTitle.vue'

export default {
  components: {EventTitle},
  mixins: [ event ],
  props: {
    idevents: {
      type: Number,
      required: true
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
  },
  methods: {
    brokenProfileImage (event) {
      event.target.src = DEFAULT_PROFILE
    },
    goto() {
      window.location = '/party/view/' + this.event.idevents
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.datebox {
  text-align: center;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }

  .month {
    line-height: 1rem;
  }
}

.date {
  line-height: 1.3rem;
  text-align: center;
  padding-top: 13px;
  width: 150px;
  font-size: 15px;
}

hr {
  border-top: 1px solid black;
}

.profile {
  border: 1px solid black;
  width: 48px;
  height: 48px;
}
</style>