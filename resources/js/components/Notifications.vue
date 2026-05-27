<template>
  <div class="badge-group">
    <button id="badge-talk-notifications" :class="{
      'badge': true,
      'badge-pill': true,
      'badge-info' : true,
      'badge-left': true,
      'badge-no-notifications' : !discourseNotifications,
      'd-flex': true
    }" @click="goto">
      <svg width="22" height="20" aria-hidden="true" data-prefix="fas" data-icon="comments" role="img"
           xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="svg-inline--fa fa-comments fa-w-18 fa-2x">
        <path fill="currentColor"
              d="M416 192c0-88.4-93.1-160-208-160S0 103.6 0 192c0 34.3 14.1 65.9 38 92-13.4 30.2-35.5 54.2-35.8 54.5-2.2 2.3-2.8 5.7-1.5 8.7S4.8 352 8 352c36.6 0 66.9-12.3 88.7-25 32.2 15.7 70.3 25 111.3 25 114.9 0 208-71.6 208-160zm122 220c23.9-26 38-57.7 38-92 0-66.9-53.5-124.2-129.3-148.1.9 6.6 1.3 13.3 1.3 20.1 0 105.9-107.7 192-240 192-10.8 0-21.3-.8-31.7-1.9C207.8 439.6 281.8 480 368 480c41 0 79.1-9.2 111.3-25 21.8 12.7 52.1 25 88.7 25 3.2 0 6.1-1.9 7.3-4.8 1.3-2.9.7-6.3-1.5-8.7-.3-.3-22.4-24.2-35.8-54.5z"></path>
      </svg>
      <div class="chat-count">
          <!-- eslint-disable-next-line-->
          <span v-html="padCount(discourseNotifications)" />
      </div>
    </button>
    <button id="notifications-badge" :class="{
      'badge': true,
      'badge-pill': true,
      'badge-info': true,
      'badge-right': true,
      'badge-no-notifications': !restartersNotifications,
      'd-flex': true
       }" data-toggle="collapse" data-target="#notifications" aria-expanded="false" aria-controls="notifications">
      <svg width="22" height="20" viewBox="0 0 11 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd"
           clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414">
        <g fill="#fff">
          <ellipse cx="5.25" cy="4.868" rx="3.908" ry="3.94"/>
          <path
              d="M4.158 13.601h2.184v.246h-.001A1.097 1.097 0 0 1 5.25 15a1.097 1.097 0 0 1-1.092-1.101l.001-.052h-.001v-.246z"/>
          <ellipse cx=".671" cy="12.337" rx=".671" ry=".677"/>
          <path d="M.671 11.66h9.158v1.353H.671z"/>
          <ellipse cx="5.25" cy=".927" rx=".92" ry=".927"/>
          <ellipse cx="9.829" cy="12.337" rx=".671" ry=".677"/>
          <path d="M1.342 4.439h7.815v8.574H1.342z"/>
          <path d="M0 12.337h10.5v.677H0z"/>
        </g>
      </svg>
      <div class="chat-count">
        <!-- eslint-disable-next-line-->
        <span v-html="padCount(restartersNotifications)" />
      </div>
    </button>
  </div>
</template>
<script>
import axios from 'axios'

export default {
  props: {
    userId: {
      type: Number,
      required: true
    },
    discourseBaseUrl: {
      type: String,
      required: true
    },
    discourseUserName: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      discourseNotifications: null,
      restartersNotifications: null
    }
  },
  computed: {
    url () {
      return this.discourseBaseUrl + '/session/sso?return_path=' + this.discourseBaseUrl + '/u/' + this.discourseUserName + '/notifications'
    },
  },
  mounted() {
    setTimeout(async() => {
      const ret = await axios.get('/api/users/' + this.userId + '/notifications/')

      if (ret.data.success) {
        this.restartersNotifications = ret.data.restarters
        this.discourseNotifications = ret.data.discourse
      }
    }, 5000)
  },
  methods: {
    padCount(val) {
      if (val === null) {
        val = '--'
      } else if (val > 99) {
        val = 99
      }

      return val
    },
    goto() {
      window.location = this.url
    }
  }
}
</script>
<style scoped lang="scss">
.chat-count {
  width: 27px;
  font-size: 16px;
  top: 2px;
}
</style>