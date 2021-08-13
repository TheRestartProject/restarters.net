<template>
  <div>
    <div class="d-none d-lg-flex">
      <b-form-timepicker class="start-time mr-1" v-model="currentStartTime" placeholder="--:--" @input="changeEndTime" hide-header :class="{ hasError: hasError }" />
      <b-form-timepicker class="ml-1 end-time" v-model="currentEndTime" placeholder="--:--" hide-header :class="{ hasError: hasError }" />
    </div>
    <div class="d-flex d-lg-none">
      <b-input size="lg" type="time" name="start" v-model="currentStartTime" :class="{ hasError: hasError, 'mr-1': true, focusfix: true }" />
      <b-input size="lg" type="time" name="end" v-model="currentEndTime"  :class="{ hasError: hasError, 'ml-1': true, focusfix: true }" />
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
  data() {
    return {
      currentStartTime: null,
      currentEndTime: null
    }
  },
  watch: {
    start: {
      handler(newVal) {
        this.currentStartTime = newVal
      },
      immediate: true
    },
    end: {
      handler(newVal) {
        this.currentEndTime = newVal
      },
      immediate: true
    },
    currentStartTime(newVal) {
      this.$emit('update:start', newVal)
    },
    currentEndTime(newVal) {
      this.$emit('update:end', newVal)
    }
  },
  methods: {
    changeEndTime: function (startTime) {
      // TODO This lets us have an end time before the start time.
      // Replicating existing functionality.
      // When start time changes, change end time to 3 hours hence.
      let timeParts = startTime.split(':');
      var hours = (parseInt(timeParts[0]) + 3).toString();

      if (hours.length < 2) {
        hours = '0' + hours;
      }

      var mins = timeParts[1];

      this.currentEndTime = hours+':'+mins
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
}

/deep/ label {
    font-weight: normal;
}

/deep/ .start-time label,
/deep/ .end-time label {
    padding: 0.5rem 0 !important;
    margin:0;
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
    margin-left: 1px !important;
}

</style>
