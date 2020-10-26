<template>
  <div class="d-flex flex-column justify-content-end height">
    <div :class="'medal-' + position">
      <div class="bg-white d-flex flex-column justify-content-between text-center font-weight-bold medal">
        <div>
          <b-img-lazy class="position-absolute rosette" :src="'/images/rosette_' + position + '_ico.svg'" />
        </div>
        <div class="d-flex flex-column">
        <span class="text-brand large">
          {{ device.counter }}
        </span>
          <span class="font-weight-bold small mb-2">
          {{ device.name }}
        </span>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    device: {
      type: Object,
      required: true
    },
    position: {
      type: Number,
      required: true
    }
  }
}
</script>
<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.height {
  height: 220px;

  /deep/ .medal-1 {
    height: 200px
  }

  /deep/ .medal-2 {
    height: 170px;
  }

  /deep/ .medal-3 {
    height: 140px;
  }
}

.medal {
  border: 1px solid black;
  text-align: center !important;
  -webkit-box-shadow: $black 5px 5px 0px 0px;
  box-shadow: $black 5px 5px 0px 0px;
  height: 100%
}

.large {
  font-size: 36px;
}

.rosette {
  width: 40px;
  transform: translate(-20px, -22px);
}
</style>