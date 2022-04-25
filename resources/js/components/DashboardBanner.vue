<template>
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed"  v-if="bigGive">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <span class='badge badge-warning'>NEW!</span>
            <strong>Help us maintain & improve this site</strong>
          </div>
          <p>
            Restarters is a non-profit platform and we need your help to keep it running. Until the 29th of April, all donations will be doubled by The Big Give, so there’s never been a better time to support us! Thank you
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
        <a href='https://donate.thebiggive.org.uk/donate/a056900002FQbXSAA1' class='btn btn-md btn-primary btn-block' title=''>Donate today & double your impact</a>
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
      id: 'biggive',

      // Change this to 'secondary' for yellow or 'danger' for pink.
      variant: 'secondary'
    }
  },
  computed: {
    bigGive() {
      var now = moment()

      return now.isBefore('2022-04-29 13:00')
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