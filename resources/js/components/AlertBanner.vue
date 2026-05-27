<template xmlns="http://www.w3.org/1999/html">
  <div>
    <b-alert v-for="alert in alertsNotDismissed" :key="'alert-' + alert.id"
             :variant="alert.variant"
             variant="secondary" dismissible class="information-alert mb-2" @dismissed="dismissed(alert.id)" show>
      <div class="d-sm-flex flex-row justify-content-between align-items-center">
        <div class="action-text-left float-left d-flex flex-row">
          <div class="action-text mb-0">
            <div class='mb-2'>
              <!-- <span class='badge badge-warning'>NEW!</span> -->
              <strong>{{ alert.title }}</strong>
            </div>
            <div v-html="alert.html" />
          </div>
        </div>

        <div class="float-right mt-3 mt-sm-0" v-if="alert.ctatitle && alert.ctalink">
          <a :href='alert.ctalink' class='btn btn-md btn-primary btn-block'
             target="_blank" rel="noopener" title=''>{{ alert.ctatitle }}</a>
        </div>
      </div>
    </b-alert>
  </div>
</template>
<script>
import moment from 'moment'

export default {
  computed: {
    alerts() {
      return this.$store.getters['alerts/list']
    },
    alertsNowOrInFuture() {
      return this.alerts.filter(alert => {
        return moment().isSameOrAfter(alert.start) && moment().isSameOrBefore(alert.end)
      })
    },
    alertsNotDismissed() {
      return this.alertsNowOrInFuture.filter(alert => {
        let ret = true

        try {
          ret = !localStorage.getItem('alert-' + alert.id)
        } catch (e) {
          console.log("Get local failed", e)
        }

        return ret
      })
    },
  },
  async mounted() {
    await this.$store.dispatch('alerts/fetch')
  },
  methods: {
    dismissed(id) {
      try {
        localStorage.setItem('alert-' + id, true)
      } catch (e) {
        console.log("Set local failed", e)
      }
    }
  }
}
</script>
