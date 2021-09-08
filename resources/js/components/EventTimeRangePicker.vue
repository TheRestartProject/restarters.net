<template>
  <div>
    <div id="sizer" ref="breakpoint" class="d-none d-md-block" />
    <div v-if="mobile" class="mobile d-flex">
      <b-form-input
          size="lg"
          type="time"
          name="start"
          v-model="currentStartTime"
          :class="{ hasError: hasError, 'mr-1': true, focusfix: true }"
          placeholder="--:--"
      />
      <b-form-input
          size="lg"
          type="time"
          name="end"
          v-model="currentEndTime"
          :class="{ hasError: hasError, 'ml-1': true, focusfix: true }"
          placeholder="--:--"
      />
    </div>
    <div v-else class="d-flex desktop justify-content-between">
      <b-form-timepicker
          class="start-time"
          name="start"
          v-model="currentPickerStartTime"
          @input="changeEndTime"
          hide-header
          :class="{ hasError: hasError, 'flex-shrink-1': true }"
          button-variant="white"
          size="lg"
          minutes-step="5"
          placeholder="--:--" />
      <b-form-timepicker
        class="end-time"
        v-model="currentPickerEndTime"
        hide-header
        name="end"
        :class="{ hasError: hasError }"
        button-variant="white"
        size="lg"
        minutes-step="5"
        placeholder="--:--" />
    </div>
  </div>
</template>
<script>
import DashboardEvent from './DashboardEvent'
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
      bump: 0,
      mobileTimer: null
    }
  },
  computed: {
    mobile() {
      // Detect breakpoint by checking computing style of an element which uses the bootstrap classes.
      let ret = false && this.bump

      const el = this.$refs.breakpoint
      console.log("Sizer", el, this.$refs, this.bump)
      if (el) {
        const display = getComputedStyle(el, null).display
        console.log(display)

        if (display === 'none') {
          ret = true
        }
      }

      return ret
    },
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
    currentStartTime(newVal) {
      this.$emit('update:start', newVal)
    },
    currentEndTime(newVal) {
      this.$emit('update:end', newVal)
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
  mounted() {
    console.log("Mounted", this.bump)
    this.checkMobile()
  },
  methods: {
    checkMobile() {
      // This is a quick and dirty way of doing v-if based on breakpoints.  We need this so that we can show the
      // native time pickers on mobile as desired.  We can't just use display classes because we'd have multiple
      // inputs with the same name.
      this.mobileTimer = setTimeout(this.checkMobile, 200)
      this.bump++;
    },
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
        }
      }
    }
  },
  beforeDestroy() {
    if (this.mobileTimer) {
      clearTimeout(this.mobileTimer)
    }
  }
}
</script>

<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';
.b-form-timepicker {
  margin: 0px;
  max-height: 42px;

  &.form-control {
    padding: 0 10px;
  }

  &:first-child {
    border-right: 0;
  }

  &.hasError {
    border: none !important;
  }
}

/deep/ label {
  font-weight: normal;
}

/deep/ .start-time label,
/deep/ .end-time label {
  padding: 0.5rem 0 !important;
  margin: 0;
  border-width: 0px !important;
  border-radius: 0;
}

/deep/ .start-time .btn,
/deep/ .end-time .btn {
  padding: 0.5rem !important;
  border: 0;
  margin: 0;
  min-width: unset !important;
}

/deep/ output {
  justify-content: center;
}

/deep/ .mobile .b-time .form-control {
  width: 100%;
  height: 100%;
  border: 0;
  padding: 0 10px;
}

/deep/ .desktop .b-form-timepicker {
  width: 125px;
}

.focusfix:focus {
  margin-top: 2px;
}

/deep/ button {
  border-radius: 0 !important;
}

/deep/ input {
  @include media-breakpoint-up(lg) {
    min-width: 6rem !important;
    max-width: 6rem !important;
  }
}

/deep/ .input-group-append {
  left: -50px;
  position: relative;
  top: 3px;
}
</style>
