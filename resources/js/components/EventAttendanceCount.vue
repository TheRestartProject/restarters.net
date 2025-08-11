<template>
  <div v-if="canedit" class="attendance-button-wrapper">
    <div class="d-flex justify-content-between">
      <b-input-group>
        <b-input-group-prepend class="d-flex flex-column justify-content-center">
          <b-btn variant="white" class="attendance-button d-grid align-content-center justify-content-center" @click="dec">
            <img class="icon" :src="imageUrl('/images/minus-icon.svg')" alt="-" title="Decrement" />
          </b-btn>
        </b-input-group-prepend>
        <b-input v-model="current" class="attendance-count pt-1 text-center" type="number" step="1" @keyup="set" />
        <b-input-group-append class="d-flex flex-column justify-content-center">
          <b-btn variant="white" class="attendance-button d-grid align-content-center justify-content-center" @click="inc">
            <img class="icon" :src="imageUrl('/images/add-icon.svg')" alt="+" title="Increment" />
          </b-btn>
        </b-input-group-append>
      </b-input-group>
    </div>
  </div>
  <div v-else class="d-flex justify-content-center">
    <div class="attendance-count pt-1">
      {{ current }}
    </div>
  </div>
</template>
<script>
import images from '../mixins/images'

export default {
  props: {
    count: {
      type: Number,
      required: false,
      default: 0
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  mixins: [images],
  data () {
    return {
      current: null
    }
  },
  mounted() {
    this.current = this.count !== null ? Math.max(this.count, 0) : 0
  },
  methods: {
    inc() {
      this.current++;
      this.$emit('change', this.current)
    },
    dec() {
      if (this.current > 0) {
        this.current--;
        this.$emit('change', this.current)
      }
    },
    set() {
      // This is triggered on keyup as the change even doesn't fire while you're still in the field.
      this.current = parseInt(this.current)

      if (this.current > 0) {
        this.$emit('change', this.current)
      } else {
        // Don't allow them to type a negative value.
        this.current = 0
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.attendance-button-wrapper {
  width: 180px;
}

.attendance-count {
  font-size: 23px;
  font-weight: bold;
  color: $brand-light;
  -webkit-appearance: none;
  -moz-appearance: textfield;

  @include media-breakpoint-down(sm) {
    font-size: 16px;
    height: 14px;
    padding-bottom: 0 !important;
    padding-top: 0 !important;
    padding-left: 0px;
    padding-right: 0px;
    max-width: 5em;
    min-height: 30px;
  }
}

.attendance-count::-webkit-outer-spin-button, .attendance-count::-webkit-inner-spin-button {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  margin: 0;
}

.attendance-button {
  width: 50px;
  height: 50px;
  background-color: $white;
  border: 1px solid $black;
  font-size: 2em;
  border-radius: 0px;
  font-family: monospace;
  text-align: center;
  padding-left: 0px;
  padding-top: 1px;
  padding-right: 0px;
  padding-bottom: 0px;

  img {
    width: 25px;
    height: 25px;
  }

  @include media-breakpoint-down(sm) {
    width: 30px;
    height: 30px;

    img {
      width: 15px;
      height: 15px;
    }
  }
}
</style>