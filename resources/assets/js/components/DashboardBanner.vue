<template>
  <b-alert :show="show" :variant="variant" dismissible class="information-alert" @dismissed="dismissed">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <div class="action-text mb-0">
          <div class='mb-2'>
            <span class='badge badge-warning'>NEW!</span>
            <strong>Recording unpowered items</strong>
          </div>
          <p>
            It is now possible to record repairs of non-electronic/electrical items at your events!
          </p>
        </div>
      </div>

      <div class="float-right mt-3 mt-sm-0">
        <a href='https://talk.restarters.net/t/you-can-now-record-unpowered-items-in-the-fixometer/3388' class='btn btn-md btn-primary btn-block' title=''>Learn how here</a>
      </div>
    </div>
  </b-alert>
</template>
<script>
export default {
  data () {
    return {
      // Change this id to something unique each time you edit this - it's used to remember not to show dismissed
      // banners.
      id: 'unpowered',

      // Change this to 'secondary' for yellow or 'danger' for pink.
      variant: 'danger'
    }
  },
  computed: {
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