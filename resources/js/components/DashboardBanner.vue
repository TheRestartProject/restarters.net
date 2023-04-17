<template>
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed"  v-if="bannerActive">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <!-- <span class='badge badge-warning'>NEW!</span> -->
            <strong>Help sustain this site today and double your impact 🤩</strong>
          </div>
          <p>
              Restarters.net is free to use, but not to run. We work hard to keep the lights on and
              <a href="https://talk.restarters.net/t/restarters-net-software-updates-changelog/1511" target="_blank">build improvements</a>.
              Until 27 April, any donation you make to Restart will be <strong>doubled</strong> by The Big Give!
              Please consider supporting us this week to help us make repair a reality and keep this site running.
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
          <a href='https://tinyurl.com/restartersbiggive' class='btn btn-md btn-primary btn-block' target="_blank" title=''>Donate</a>
      </div>
    </div>
  </b-alert>
</template>
<script>
import moment from 'moment'

export default {
  data () {
    return {
      // Change this id to something unique each time you edit this - it's used to remember not to show dismissed
      // banners.
      id: 'biggive2023',

      // Change this to 'secondary' for yellow or 'danger' for pink.
      variant: 'secondary'
    }
  },
  computed: {
    bannerActive() {
      var now = moment()

      return now.isAfter('2023-04-20 00:01') && now.isBefore('2023-04-27 23:59')
    },
    show() {
      let ret = true

      try {
        ret = !localStorage.getItem('banner-' + this.id)
      } catch (e) {
        console.log("Get local failed", e)
      }

      return ret
    }
  },
  methods: {
    dismissed() {
      try {
        localStorage.setItem('banner-' + this.id, true)
      } catch (e) {
        console.log("Set local failed", e)
      }
    }
  }
}
</script>
