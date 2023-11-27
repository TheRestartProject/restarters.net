<template xmlns="http://www.w3.org/1999/html">
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed"  v-if="bannerActive">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <!-- <span class='badge badge-warning'>NEW!</span> -->
            <strong>Support our work!</strong>
          </div>
          <p>
            We’re participating in the
            <a href="https://www.avivacommunityfund.co.uk/p/the-restart-project-1" target="_blank" rel="noopener">Aviva crowdfunder</a>
            this year. Until the end of 2023 every donation will be doubled!
          </p>
          <p>
            Find out more about how your donation will support our work
            <a href="https://talk.restarters.net/t/were-participating-in-the-aviva-crowdfunder/18990" target="_blank" rel="noopener">here</a>.
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
          <a href='https://www.avivacommunityfund.co.uk/p/the-restart-project-1' class='btn btn-md btn-primary btn-block' target="_blank" rel="noopener" title=''>Double your donation now</a>
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
      id: 'aviva-crowdfunder-2023',

      // Change this to 'secondary' for yellow or 'danger' for pink.
      variant: 'secondary'
    }
  },
  computed: {
    bannerActive() {
      var now = moment()

      return now.isAfter('2023-11-27 00:00') && now.isBefore('2023-12-31 23:59')
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
