<template>
    <div>
    <b-row class="no-gutters">
        <b-col>
            <b-form-timepicker id="start-time" v-model="startTime" placeholder="--:--" @input="changeEndTime" />
            <input type="hidden" name="start" :value="startTime" />
        </b-col>
        <b-col>
            <b-form-timepicker id="end-time" v-model="endTime" placeholder="--:--" />
            <input type="hidden" name="end" :value="endTime" />
        </b-col>
    </b-row>
    </div>
</template>

<script>
export default {
  props: ['starttimeinit', 'endtimeinit'  ],
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
.b-form-timepicker.form-control {
    padding: 0 10px;
}

/deep/ .b-time .form-control {
    width: 100%;
    height: 100%;
    border: 0;
    padding: 0 10px;
}

/deep/ #start-time, /deep/ #end-time {
    padding: 0;
}

/deep/ #start-time__value_, /deep/ #end-time__value_ {
    border: 0;
    margin: 0;
}
</style>
