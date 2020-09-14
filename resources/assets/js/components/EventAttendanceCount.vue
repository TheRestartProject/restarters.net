<template>
  <div class="d-flex justify-content-between attendance-button-wrapper">
    <b-btn variant="white" class="attendance-button">
      -
    </b-btn>
    <div class="attendance-count pt-1">
      {{ count }}
    </div>
    <b-btn variant="white" class="attendance-button">
      +
    </b-btn>
  </div>
</template>
<script>
export default {
  props: {
    count: {
      type: Number,
      required: true
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
  font-size: 24px;
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