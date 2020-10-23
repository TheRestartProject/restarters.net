<template>
    <div class="d-flex">
        <b-form-timepicker class="start-time" v-model="startTime" placeholder="--:--" @input="changeEndTime" />
        <input type="hidden" name="start" :value="startTime" />
        <b-form-timepicker class="end-time" v-model="endTime" placeholder="--:--" />
        <input type="hidden" name="end" :value="endTime" />
    </div>
</template>

<script>
export default {
  props: {
    starttimeinit: {
      required: false,
      type: String
    },
    endtimeinit: {
      required: false,
      type: String
    }
  },
  data() {
    return {
      startTime: this.starttimeinit,
      endTime: this.endtimeinit
    }
  },
  methods: {
    changeEndTime: function (startTime) {
      // Replicating existing functionality.
      // When start time changes, change end time to 3 hours hence.
      let timeParts = startTime.split(':');
      var hours = (parseInt(timeParts[0]) + 3).toString();

      if (hours.length < 2) {
        hours = '0' + hours;
      }

      var mins = timeParts[1];

      this.endTime = hours+':'+mins
    }
  }
}
</script>

<style scoped lang="scss">
.b-form-timepicker {
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
    border: 0;
    margin:0;
}

/deep/ .start-time .btn,
/deep/ .end-time .btn {
    padding: 0.5rem !important;
    border: 0;
    margin: 0;
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
</style>
