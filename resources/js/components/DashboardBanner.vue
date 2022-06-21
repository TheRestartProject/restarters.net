<template>
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed"  v-if="bannerActive">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <span class='badge badge-warning'>NEW!</span>
            <strong>Help us maintain & improve this site</strong>
          </div>
          <p>
              Restarters is a non-profit platform and we need your help to keep it running. Until the 30th of June, vote for Restart as your favourite charity, and we could be on Give at Checkout with PayPal AND receive matched donations.  You can find more information <a href="https://talk.restarters.net/t/9197">here</a>.  Thank you!
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
          <a href='https://forms.office.com/pages/responsepage.aspx?id=FHkA-yBgdEOXfiG6xfP0yLpJiqalbJRLvNFodrLa3cxUOElMV1YxTUlKVTZYMENTTEtZNlQ0QjJUTS4u' class='btn btn-md btn-primary btn-block' title=''>Vote now</a>
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

      return now.isBefore('2022-06-30 17:00')
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
