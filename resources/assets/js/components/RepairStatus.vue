<template>
  <div>
    Repair status for {{ device.iddevices }}
    {{ $lang.get('partials.fixed')}}
  </div>
</template>
<script>
export default {
  props: {
    device: {
      type: Object,
      required: true
    }
  }
}
</script>