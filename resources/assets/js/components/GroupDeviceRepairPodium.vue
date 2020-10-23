<template>
  <div :class="'bg-white d-flex flex-column text-center medal-' + position">
    <span>
      {{ position }}
    </span>
    <span class="text-brand large">
      {{ device.counter }}
    </span>
    <span>
      {{ device.name }}
    </span>
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