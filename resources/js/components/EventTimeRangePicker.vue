<template>
  <div>
    <div class="d-none d-lg-flex">
      <b-input-group>
        <b-form-input
            size="lg"
            type="text"
            name="start"
            v-model="currentStartTime"
            :class="{ hasError: hasError, 'mr-1': true, focusfix: true }"
            placeholder="--:--"
        />
        <b-input-group-append>
          <b-form-timepicker
              class="d-none d-lg-block start-time"
              v-model="currentPickerStartTime"
              @input="changeEndTime"
              hide-header
              :class="{ hasError: hasError }"
              button-only
              button-variant="white"
              size="sm"
              minutes-step="5"
              dropleft />
        </b-input-group-append>
      </b-input-group>
      <b-input-group>
        <b-form-input
            size="lg"
            type="text"
            name="end"
            v-model="currentEndTime"
            :class="{ hasError: hasError, 'ml-1': true, focusfix: true }"
            placeholder="--:--"
        />
        <b-input-group-append>
          <b-form-timepicker
              :key="bump"
              class="d-none d-lg-block end-time btn-white"
              v-model="currentPickerEndTime"
              hide-header
              :class="{ hasError: hasError }"
              button-only
              button-variant="white"
              size="sm"
              minutes-step="5"
              dropleft />
        </b-input-group-append>
      </b-input-group>
    </div>
    <div class="d-flex d-lg-none">
      <b-form-input
          size="lg"
          type="text"
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
  </div>
</template>
<script>
export default {
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
      bump: 0
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
          } else {
            // We prevent end times before start times.  This is slightly clunky - we can't seem to update the
            // value in timepicker while it's open, so trigger a re-render by changing the key.
            this.$emit('update:end', oldVal)
            this.currentEndTime = this.currentStartTime
            this.bump++
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
        }
      }
    }
  }
}
</script>

<style scoped lang="scss">
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

/deep/ .b-time .form-control {
  width: 100%;
  height: 100%;
  border: 0;
  padding: 0 10px;
}

.focusfix:focus {
  margin-top: 2px;
}

/deep/ button {
  border-radius: 0 !important;
}

/deep/ input {
  min-width: 6rem !important;
  max-width: 6rem !important;
}

/deep/ .input-group-append {
  left: -50px;
  position: relative;
  top: 3px;
}
</style>
