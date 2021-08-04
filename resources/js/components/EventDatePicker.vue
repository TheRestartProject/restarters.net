<template>
    <div>
        <b-form-datepicker class="datepicker" v-model="value" hide-header></b-form-datepicker>
        <input type="hidden" name="event_date" :value="value" />
    </div>
</template>

<script>
export default {
  props: {
    date : {
      required: false,
      type: String
    }
  },
  data() {
    return {
      value: this.date,
    }
  },
  watch: {
    value(newVal) {
      this.$emit('update:date', newVal)
    }
  }
}
</script>

<style scoped lang="scss">
@import 'resources/global/css/_variables';

/deep/ .datepicker {
    margin: 0px;

    & label {
        padding-bottom: 0;
        margin: 0;
        font-weight: normal;
        border-width: 0px !important;
    }

    .btn {
        padding: 0.4rem 0.6rem !important;
    }

    .btn-primary {
        background-color: $brand-orange !important;
        color: $black !important;
    }

    .btn {
      min-width: unset !important;
    }
}
</style>
