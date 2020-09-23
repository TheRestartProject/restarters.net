<template>
  <div v-if="canedit" class="attendance-button-wrapper">
    <div class="d-flex justify-content-between">
      <b-input-group>
        <b-input-group-prepend>
          <b-btn variant="white" class="attendance-button" @click="dec">
            -
          </b-btn>
        </b-input-group-prepend>
        <b-input v-model="current" class="attendance-count pt-1 text-center" type="number" step="1" @keyup="set" />
        <b-input-group-append>
          <b-btn variant="white" class="attendance-button" @click="inc">
            +
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
export default {
  props: {
    count: {
      type: Number,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      current: null
    }
  },
  mounted() {
    this.current = this.count
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
      this.$emit('change', this.current)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.attendance-button-wrapper {
  width: 180px;
}

.attendance-count {
  font-size: 23px;
  font-weight: bold;
  color: $brand-light;
}

.attendance-button {
  width: 50px;
  height: 50px;
  background-color: $white;
  border: 1px solid $black;
  font-size: 2em;
  border-radius: 0px;
  padding-top: 3px;
  padding-left: 15px;
  font-family: monospace;
  text-align: center;
}

</style>