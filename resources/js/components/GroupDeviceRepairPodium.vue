<template>
  <div>
    <div class="d-none d-md-flex flex-column justify-content-end height">
      <div :class="'medal-' + position">
        <div class="bg-white d-flex flex-column justify-content-between text-center font-weight-bold medal">
          <div>
            <b-img-lazy class="position-absolute rosette shift" :src="'/images/rosette_' + position + '_ico.svg'" />
          </div>
          <div class="d-flex flex-column">
          <span class="text-brand large">
            {{ device.counter }}
          </span>
          <span class="font-weight-bold small mb-2">
            {{ translatedName }}
          </span>
          </div>
        </div>
      </div>
    </div>
    <div class="d-block d-md-none">
      <div class="mobile mb-2 border-black">
        <b-img-lazy :src="'/images/rosette_' + position + '_ico.svg'" class="mr-3 mb-2 rosette" />
        <span class="text-brand large mr-3">
          {{ device.counter }}
        </span>
        <span class="font-weight-bold align-content-center mt-2">
          {{ device.name }}
        </span>
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
  },
  computed: {
    translatedName() {
      return this.$lang.get('strings.' + this.device.name)
    }
  }
}
</script>
<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.height {
  height: 152px;

  ::v-deep .medal-1 {
    height: 152px
  }

  ::v-deep .medal-2 {
    height: 132px;
  }

  ::v-deep .medal-3 {
    height: 112px;
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

  &.shift {
    transform: translate(-20px, -22px);
  }
}

.border-black {
  border-bottom: 1px solid black;
}

.mobile {
  display: grid;
  grid-template-columns: 50px 4rem 1fr;
}
</style>