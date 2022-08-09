<template>
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed"  v-if="bannerActive">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <span class='badge badge-warning'>NEW!</span>
            <strong>Are you a Paypal user? Help us maintain & improve this site &#128154;</strong>
          </div>
          <p>
            As people interested in repair, we all understand the importance of maintenance. It’s not always flashy,
            but it is fundamental and Restarters.net is no exception. Right now, we’re asking you to help by making
            a donation, if you can. Throughout August, <b>all donations will be doubled by Paypal Giving Fund</b>, so
            there’s never been a better time to support us! Thank you &#128591;
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
          <a href='https://www.paypal.com/gb/fundraiser/charity/61071' class='btn btn-md btn-primary btn-block' title=''>Donate now</a>
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
      id: 'paypal',

      // Change this to 'secondary' for yellow or 'danger' for pink.
      variant: 'secondary'
    }
  },
  computed: {
    bannerActive() {
      var now = moment()

      return now.isBefore('2022-08-31 12:00')
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
