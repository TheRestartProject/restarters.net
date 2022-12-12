<template>
    <div>
      <b-form-datepicker
          v-model="value"
          hide-header
          :class="{ hasError: hasError, datepicker: true, 'd-none': true, 'd-lg-flex': true }"
          :date-format-options="{ weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }"
      />
      <b-input size="lg" type="date" name="event_date" v-model="value" class="d-block d-lg-none focusfix" />
    </div>
</template>

<script>
export default {
  props: {
    date : {
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
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

::v-deep .datepicker {
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

::v-deep .d-none {
  display: none !important;
}

::v-deep .d-lg-flex {
  @include media-breakpoint-up(lg) {
    display: flex !important;
  }
}

::v-deep .focusfix:focus {
  margin-top: 2px;
  margin-left: 1px;
  margin-bottom: 1px;
}

</style>
