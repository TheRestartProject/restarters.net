<template>
    <div>
        <b-form-datepicker class="datepicker" v-model="value"></b-form-datepicker>
        <input type="hidden" name="event_date" :value="value" />
    </div>
</template>

<script>
export default {
  props: {
    'initialvalue' : {
      required: false,
      type: String
    }
  },
  data() {
    return {
      value: this.initialvalue,
    }
  },
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';

.b-form-datepicker.form-control {
    padding: 0 10px;
}

/deep/ .datepicker {
    & label {
        padding-bottom: 0;
        border: 0;
        margin: 0;
        font-weight: normal;
    }

    .btn {
        padding: 0.4rem 0.3rem !important;
    }

    .btn-primary {
        background-color: $brand-orange !important;
        color: $black !important;
    }
}
</style>
