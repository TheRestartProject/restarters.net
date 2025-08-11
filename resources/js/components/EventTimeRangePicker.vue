<template>
  <div class="d-flex justify-content-between">
    <b-form-input
        size="lg"
        type="time"
        name="start"
        v-model="currentStartTime"
        :class="{ hasError: hasError, 'mr-1': true, time: true }"
        placeholder="--:--"
        @blur="changeCurrentStartTime"
    />
    <b-form-input
        size="lg"
        type="time"
        name="end"
        v-model="currentEndTime"
        :class="{ hasError: hasError, 'ml-1': true, time: true }"
        placeholder="--:--"
        @blur="changeCurrentEndTime"
    />
  </div>
</template>
<script>
import DashboardEvent from './DashboardEvent.vue'
export default {
  components: {DashboardEvent},
  props: {
    start: {
      required: false,
      type: String
    },
    end: {
      required: false,
      type: String
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentStartTime: null,
      currentEndTime: null,
      currentPickerStartTime: null,
      currentPickerEndTime: null,
    }
  },
  watch: {
    start: {
      handler (newVal) {
        if (newVal) {
          this.currentStartTime = newVal.substring(0, 5)
          this.changeEndTime(newVal)
        }
      },
      immediate: true
    },
    end: {
      handler (newVal, oldVal) {
        if (newVal) {
          if (newVal >= this.currentStartTime) {
            this.currentEndTime = newVal.substring(0, 5)
            this.$nextTick(() => {
              this.$emit('update:end', this.currentEndTime)
            })
          } else {
            // We prevent end times before start times.  This is slightly clunky - we can't seem to update the
            // value in timepicker while it's open, so trigger a re-render by changing the key.
            this.currentEndTime = this.currentStartTime
            this.$nextTick(() => {
              this.$emit('update:end', oldVal)
            })
          }
        }
      },
      immediate: true
    },
    currentPickerStartTime(newVal) {
      // Trim seconds
      if (newVal) {
        this.currentStartTime = newVal.substring(0, 5)
      }
    },
    currentPickerEndTime(newVal) {
      // Trim seconds
      if (newVal) {
        this.currentEndTime = newVal.substring(0, 5)
      }
    }
  },
  methods: {
    changeEndTime: function (startTime) {
      if (startTime && startTime.match(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)) {
        // When start time changes, change end time to 3 hours hence (if there's enough hours in the day).
        let timeParts = startTime.split(':')
        let hours = parseInt(timeParts[0])

        if (hours < 21) {
          hours = (hours + 3).toString()

          if (hours.length < 2) {
            hours = '0' + hours
          }

          var mins = timeParts[1]

          this.currentEndTime = hours + ':' + mins
          this.$nextTick(() => {
            this.$emit('update:end', this.currentEndTime)
          })
        }
      }
    },
    changeCurrentStartTime() {
      this.$emit('update:start', this.currentStartTime)
    },
    changeCurrentEndTime() {
      this.$emit('update:end', this.currentEndTime)
    },
  }
}
</script>

<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';
::v-deep label {
  font-weight: normal;
}

::v-deep .time {
  width: 125px;
  margin: 1px;

  &:focus {
    margin: 0px;
    margin-bottom: 0px;
    border: 3px solid #222!important;
  }
}
</style>
